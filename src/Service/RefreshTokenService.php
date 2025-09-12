<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class RefreshTokenService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function createRefreshToken(User $user): RefreshToken
    {
        $this->invalidateUserTokens($user);

        $refreshToken = new RefreshToken();
        $refreshToken->setToken(Uuid::v4()->toRfc4122());
        $refreshToken->setUserEntity($user);
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $refreshToken->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($refreshToken);
        $this->em->flush();

        $this->logger->info('Refresh token created', [
            'user_id' => $user->getId(),
            'token_id' => $refreshToken->getId(),
        ]);

        return $refreshToken;
    }

    public function validateRefreshToken(string $token): ?User
    {
        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findOneBy([
                'token' => $token,
                'isRevoked' => false
            ]);

        if (!$refreshToken || $refreshToken->getExpiresAt() < new \DateTimeImmutable()) {
            $this->logger->warning('Refresh token validation failed', ['token' => $token]);
            return null;
        }

        $this->logger->info('Refresh token validated successfully', [
            'token_id' => $refreshToken->getId(),
            'user_id' => $refreshToken->getUserEntity()->getId(),
        ]);

        return $refreshToken->getUserEntity();
    }

    public function refreshToken(string $oldToken): RefreshToken
    {
        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $oldToken]);

        if ($refreshToken) {
            $refreshToken->setIsRevoked(true);
            $this->em->flush();
        }

        $newToken = $this->createRefreshToken($refreshToken->getUserEntity());

        $this->logger->info('Refresh token refreshed', [
            'old_token' => $oldToken,
            'new_token_id' => $newToken->getId(),
        ]);

        return $newToken;
    }

    public function invalidateRefreshToken(string $token): void
    {
        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $token]);

        if ($refreshToken) {
            $refreshToken->setIsRevoked(true);
            $this->em->flush();

            $this->logger->info('Refresh token invalidated', ['token_id' => $refreshToken->getId()]);
        }
    }

    public function invalidateUserTokens(User $user): void
    {
        $tokens = $this->em->getRepository(RefreshToken::class)
            ->findBy(['userEntity' => $user, 'isRevoked' => false]);

        foreach ($tokens as $token) {
            $token->setIsRevoked(true);
        }

        $this->em->flush();

        $this->logger->info('All user refresh tokens invalidated', ['user_id' => $user->getId()]);
    }
}

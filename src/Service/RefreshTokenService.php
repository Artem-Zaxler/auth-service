<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class RefreshTokenService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function createRefreshToken(User $user): RefreshToken
    {
        // Инвалидируем старые токены пользователя
        $this->invalidateUserTokens($user);

        $refreshToken = new RefreshToken();
        $refreshToken->setToken(Uuid::v4()->toRfc4122());
        $refreshToken->setUserEntity($user);
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $refreshToken->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($refreshToken);
        $this->em->flush();

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
            return null;
        }

        return $refreshToken->getUserEntity();
    }

    public function refreshToken(string $oldToken): RefreshToken
    {
        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $oldToken]);

        if ($refreshToken) {
            $refreshToken->setIsRevoked(true);
        }

        return $this->createRefreshToken($refreshToken->getUserEntity());
    }

    public function invalidateRefreshToken(string $token): void
    {
        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $token]);

        if ($refreshToken) {
            $refreshToken->setIsRevoked(true);
            $this->em->flush();
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
    }
}

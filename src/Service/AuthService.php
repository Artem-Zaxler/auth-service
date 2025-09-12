<?php

namespace App\Service;

use App\Entity\User;
use App\Dto\LoginDto;
use App\Repository\UserRepository;
use App\Dto\UserDtoMapper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
        private UserDtoMapper $userDtoMapper,
        private LoggerInterface $logger
    ) {}

    public function authenticate(LoginDto $loginDto): array
    {
        $user = $this->userRepository->findOneBy(['username' => $loginDto->username]);
        if (!$user) {
            $this->logger->warning('Authentication failed: user not found', ['username' => $loginDto->username]);
            throw new AuthenticationException('Invalid credentials');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $loginDto->password)) {
            $this->logger->warning('Authentication failed: invalid password', ['username' => $loginDto->username]);
            throw new AuthenticationException('Invalid credentials');
        }

        if ($user->isBlocked()) {
            $this->logger->warning('Authentication failed: user is blocked', ['user_id' => $user->getId()]);
            throw new AuthenticationException('User is blocked');
        }

        $userDto = $this->userDtoMapper->mapUserToDto($user);

        $this->logger->info('User authenticated successfully', ['user_id' => $user->getId()]);

        return [
            'user' => $userDto,
        ];
    }

    public function logout(User $user): void
    {
        $userIdentifier = $user->getUserIdentifier();
        $connection = $this->em->getConnection();

        try {
            $connection->beginTransaction();

            $accessTokenStmt = $connection->prepare('
            UPDATE oauth2_access_token 
            SET revoked = true 
            WHERE user_identifier = :user_identifier AND revoked = false
        ');
            $accessTokenStmt->bindValue('user_identifier', $userIdentifier);
            $accessTokensRevoked = $accessTokenStmt->executeStatement();

            $refreshTokenStmt = $connection->prepare('
            UPDATE oauth2_refresh_token rt
            INNER JOIN oauth2_access_token at ON rt.access_token = at.identifier
            SET rt.revoked = true 
            WHERE at.user_identifier = :user_identifier AND rt.revoked = false
        ');
            $refreshTokenStmt->bindValue('user_identifier', $userIdentifier);
            $refreshTokensRevoked = $refreshTokenStmt->executeStatement();

            $connection->commit();

            $this->logger->info('All user tokens revoked successfully', [
                'user_id' => $user->getId(),
                'user_identifier' => $userIdentifier,
                'access_tokens_revoked' => $accessTokensRevoked,
                'refresh_tokens_revoked' => $refreshTokensRevoked
            ]);
        } catch (\Exception $e) {
            $connection->rollBack();

            $this->logger->error('Failed to revoke user tokens', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException('Failed to logout: ' . $e->getMessage());
        }
    }
}

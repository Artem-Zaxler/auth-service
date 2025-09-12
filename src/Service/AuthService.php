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
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private JWTTokenManagerInterface $jwtManager,
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

        $token = $this->jwtManager->create($user);
        $this->em->flush();

        $userDto = $this->userDtoMapper->mapUserToDto($user);

        $this->logger->info('User authenticated successfully', ['user_id' => $user->getId()]);

        return [
            'user' => $userDto,
            'token' => $token,
        ];
    }

    public function generateToken(User $user): string
    {
        $token = $this->jwtManager->create($user);
        $this->em->flush();

        $this->logger->info('Token generated for user', ['user_id' => $user->getId()]);

        return $token;
    }

    public function refreshToken(User $user): string
    {
        if ($user->isBlocked()) {
            $this->logger->warning('Refresh token failed: user is blocked', ['user_id' => $user->getId()]);
            throw new AuthenticationException('User is blocked');
        }

        $token = $this->generateToken($user);

        $this->logger->info('Token refreshed for user', ['user_id' => $user->getId()]);

        return $token;
    }
}

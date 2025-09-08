<?php

namespace App\Service;

use App\Entity\User;
use App\Dto\LoginDto;
use App\Repository\UserRepository;
use App\Service\Dto\UserDtoMapper;
use Doctrine\ORM\EntityManagerInterface;
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
    ) {}

    public function authenticate(LoginDto $loginDto): array
    {
        $user = $this->userRepository->findOneBy(['username' => $loginDto->username]);

        if (!$user) {
            throw new AuthenticationException('Invalid credentials');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $loginDto->password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        if ($user->isBlocked()) {
            throw new AuthenticationException('User is blocked');
        }

        $token = $this->jwtManager->create($user);
        $this->em->flush();

        $userDto = $this->userDtoMapper->mapUserToDto($user);

        return [
            'user' => $userDto,
            'token' => $token,
        ];
    }

    public function generateToken(User $user): string
    {
        $token = $this->jwtManager->create($user);
        $this->em->flush();
        return $token;
    }

    public function refreshToken(User $user): string
    {
        if ($user->isBlocked()) {
            throw new AuthenticationException('User is blocked');
        }

        return $this->generateToken($user);
    }
}

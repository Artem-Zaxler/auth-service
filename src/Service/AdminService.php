<?php

namespace App\Service;

use App\Entity\User;
use App\Dto\CreateUserRequestDto;
use App\Dto\UpdateUserRequestDto;
use App\Dto\GetUserByIdRequestDto;
use App\Dto\DeleteUserRequestDto;
use App\Repository\UserRepository;
use App\Dto\GetPaginatedUsersRequestDto;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function getPaginatedUsers(GetPaginatedUsersRequestDto $dto): array
    {
        $page = $dto->page;
        $limit = $dto->limit;

        $users = $this->userRepository->findAllPaginated($page, $limit);
        $total = $this->userRepository->countAll();
        $totalPages = ceil($total / $limit);

        return [
            'users' => $users,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ];
    }

    public function getUserById(GetUserByIdRequestDto $dto): User
    {
        $user = $this->userRepository->find($dto->id);

        if (!$user) {
            throw new \RuntimeException('Пользователь не найден');
        }

        return $user;
    }

    public function createUser(CreateUserRequestDto $dto): void
    {
        if ($this->userRepository->findByUsername($dto->username)) {
            throw new \RuntimeException('Пользователь с таким именем уже существует');
        }

        if ($this->userRepository->findByEmail($dto->email)) {
            throw new \RuntimeException('Пользователь с таким email уже существует');
        }

        $user = new User();
        $user->setUsername($dto->username);
        $user->setEmail($dto->email);
        $user->setRoles($dto->roles);
        $user->setIsBlocked($dto->isBlocked);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }

    public function updateUser(UpdateUserRequestDto $dto): void
    {
        $user = $this->userRepository->find($dto->id);

        if (!$user) {
            throw new \RuntimeException('Пользователь не найден');
        }

        if ($user->getUsername() !== $dto->username) {
            $existingUser = $this->userRepository->findByUsername($dto->username);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                throw new \RuntimeException('Пользователь с таким именем уже существует');
            }
        }

        if ($user->getEmail() !== $dto->email) {
            $existingUser = $this->userRepository->findByEmail($dto->email);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                throw new \RuntimeException('Пользователь с таким email уже существует');
            }
        }

        $user->setUsername($dto->username);
        $user->setEmail($dto->email);
        $user->setRoles($dto->roles);
        $user->setIsBlocked($dto->isBlocked);
        $user->setUpdatedAt(new \DateTimeImmutable());

        if (!empty($dto->password)) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPassword($hashedPassword);
        }

        $this->userRepository->save($user);
    }

    public function deleteUser(DeleteUserRequestDto $dto): void
    {
        $user = $this->userRepository->find($dto->id);

        if (!$user) {
            throw new \RuntimeException('Пользователь не найден');
        }

        $this->userRepository->remove($user);
    }
}

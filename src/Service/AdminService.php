<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Session;
use Psr\Log\LoggerInterface;
use App\Dto\CreateUserRequestDto;
use App\Dto\DeleteUserRequestDto;
use App\Dto\UpdateUserRequestDto;
use App\Dto\GetUserByIdRequestDto;
use App\Repository\UserRepository;
use App\Dto\GetSessionByIdRequestDto;
use App\Repository\SessionRepository;
use App\Dto\GetPaginatedUsersRequestDto;
use App\Dto\GetPaginatedSessionsRequestDto;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminService
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionRepository $sessionRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger
    ) {}

    public function getPaginatedUsers(GetPaginatedUsersRequestDto $dto): array
    {
        try {
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
                'totalPages' => $totalPages,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch paginated users', [
                'page' => $dto->page,
                'limit' => $dto->limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Ошибка при получении списка пользователей');
        }
    }

    public function getUserById(GetUserByIdRequestDto $dto): User
    {
        try {
            $user = $this->userRepository->find($dto->id);

            if (!$user) {
                $this->logger->warning('User not found', ['user_id' => $dto->id]);
                throw new \RuntimeException('Пользователь не найден');
            }

            $this->logger->info('User found', ['user_id' => $user->getId()]);
            return $user;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user by ID', [
                'user_id' => $dto->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Ошибка при получении пользователя');
        }
    }

    public function createUser(CreateUserRequestDto $dto): void
    {
        try {
            if ($this->userRepository->findByUsername($dto->username)) {
                $this->logger->warning('Username already exists', ['username' => $dto->username]);
                throw new \RuntimeException('Пользователь с таким именем уже существует');
            }

            if ($this->userRepository->findByEmail($dto->email)) {
                $this->logger->warning('Email already exists', ['email' => $dto->email]);
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

            $this->logger->info('User created successfully', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail()
            ]);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create user', [
                'username' => $dto->username,
                'email' => $dto->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Ошибка при создании пользователя');
        }
    }

    public function updateUser(UpdateUserRequestDto $dto): void
    {
        try {
            $user = $this->userRepository->find($dto->id);

            if (!$user) {
                $this->logger->warning('User not found for update', ['user_id' => $dto->id]);
                throw new \RuntimeException('Пользователь не найден');
            }

            if ($user->getUsername() !== $dto->username) {
                $existingUser = $this->userRepository->findByUsername($dto->username);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->logger->warning('Username already exists during update', [
                        'username' => $dto->username,
                        'user_id' => $dto->id
                    ]);

                    throw new \RuntimeException('Пользователь с таким именем уже существует');
                }
            }

            if ($user->getEmail() !== $dto->email) {
                $existingUser = $this->userRepository->findByEmail($dto->email);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    $this->logger->warning('Email already exists during update', [
                        'email' => $dto->email,
                        'user_id' => $dto->id
                    ]);

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

                $this->logger->info('Password updated for user', ['user_id' => $user->getId()]);
            }

            $this->userRepository->save($user);

            $this->logger->info('User updated successfully', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail()
            ]);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user', [
                'user_id' => $dto->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Ошибка при обновлении пользователя');
        }
    }

    public function deleteUser(DeleteUserRequestDto $dto): void
    {
        try {
            $user = $this->userRepository->find($dto->id);

            if (!$user) {
                $this->logger->warning('User not found for deletion', ['user_id' => $dto->id]);
                throw new \RuntimeException('Пользователь не найден');
            }

            $this->userRepository->remove($user);

            $this->logger->info('User deleted successfully', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername()
            ]);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user', [
                'user_id' => $dto->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Ошибка при удалении пользователя');
        }
    }

    public function getPaginatedUserSessions(GetPaginatedSessionsRequestDto $dto): array
    {
        try {
            $page = $dto->page;
            $limit = $dto->limit;

            $sessions = $this->sessionRepository->findByUserPaginated($dto->userId, $page, $limit);
            $total = $this->sessionRepository->countByUser($dto->userId);
            $totalPages = ceil($total / $limit);

            return [
                'sessions' => $sessions,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user sessions', [
                'user_id' => $dto->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Ошибка при получении сессий пользователя');
        }
    }

    public function getSessionById(GetSessionByIdRequestDto $dto): Session
    {
        try {
            $session = $this->sessionRepository->findById($dto->id);

            if (!$session) {
                $this->logger->warning('Session not found', ['session_id' => $dto->id]);
                throw new \RuntimeException('Сессия не найдена');
            }

            $this->logger->info('Session found', ['session_id' => $session->getId()]);
            return $session;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get session by ID', [
                'session_id' => $dto->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Ошибка при получении сессии');
        }
    }
}

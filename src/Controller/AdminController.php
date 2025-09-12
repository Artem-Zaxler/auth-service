<?php

namespace App\Controller;

use App\Dto\ApiResponseDto;
use Psr\Log\LoggerInterface;
use App\Service\AdminService;
use App\Dto\CreateUserRequestDto;
use App\Dto\DeleteUserRequestDto;
use App\Dto\UpdateUserRequestDto;
use App\Dto\GetUserByIdRequestDto;
use App\Dto\GetSessionByIdRequestDto;
use App\Dto\GetPaginatedUsersRequestDto;
use App\Dto\GetPaginatedSessionsRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AdminController extends AbstractController
{
    public function __construct(
        private AdminService $adminService,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    #[Route("/admin/auth/login", name: "admin_auth_login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted("ROLE_ADMIN")) {
            return $this->redirectToRoute("admin_users_index");
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render("admin/auth/login.html.twig", [
            'error' => $error,
            'last_username' => $lastUsername
        ]);
    }

    #[Route(path: "/admin/users", name: "admin_users_index", methods: ["GET"])]
    public function index(Request $request): Response
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = max(1, $request->query->getInt('limit', 20));

            $dto = new GetPaginatedUsersRequestDto();
            $dto->page = $page;
            $dto->limit = $limit;

            $result = $this->adminService->getPaginatedUsers($dto);

            return $this->render("admin/users/index.html.twig", [
                'users' => $result['users'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages']
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Ошибка при загрузке списка пользователей');
            return $this->redirectToRoute('admin_users_index');
        }
    }

    #[Route(path: "/admin/users/create", name: "admin_users_create", methods: ["GET", "POST"])]
    public function createUser(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = $request->request->all();
                $dto = CreateUserRequestDto::fromArray($data);

                $errors = $this->validator->validate($dto);
                if (count($errors) > 0) {
                    $this->logger->warning('Validation failed for user creation', [
                        'errors' => array_map(fn($e) => [
                            'field' => $e->getPropertyPath(),
                            'message' => $e->getMessage()
                        ], iterator_to_array($errors))
                    ]);

                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                } else {
                    $this->adminService->createUser($dto);

                    $this->logger->info('User created successfully', [
                        'username' => $dto->username,
                        'email' => $dto->email
                    ]);

                    $this->addFlash('success', 'Пользователь успешно создан');
                    return $this->redirectToRoute('admin_users_index');
                }
            } catch (\RuntimeException $e) {
                $this->logger->error('Failed to create user', [
                    'error' => $e->getMessage(),
                    'data' => $data ?? []
                ]);

                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->logger->error('Unexpected error during user creation', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->addFlash('error', 'Произошла непредвиденная ошибка');
            }
        }

        return $this->render("admin/users/create.html.twig", [
            'available_roles' => ['ROLE_USER', 'ROLE_ADMIN']
        ]);
    }

    #[Route(path: "/admin/users/{id}", name: "admin_users_view", methods: ["GET"])]
    public function viewUser(int $id): Response
    {
        try {
            $dto = new GetUserByIdRequestDto();
            $dto->id = $id;

            $user = $this->adminService->getUserById($dto);

            $this->logger->info('User viewed', ['user_id' => $id]);

            return $this->render("admin/users/view.html.twig", [
                'user' => $user
            ]);
        } catch (\RuntimeException $e) {
            $this->logger->error('User not found', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            throw $this->createNotFoundException('Пользователь не найден');
        } catch (\Exception $e) {
            $this->logger->error('Failed to view user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Ошибка при загрузке пользователя');
            return $this->redirectToRoute('admin_users_index');
        }
    }

    #[Route(path: "/admin/users/{id}/edit", name: "admin_users_edit", methods: ["GET", "POST"])]
    public function editUser(Request $request, int $id): Response
    {
        try {
            $getUserDto = new GetUserByIdRequestDto();
            $getUserDto->id = $id;

            $user = $this->adminService->getUserById($getUserDto);

            if ($request->isMethod('POST')) {
                try {
                    $data = $request->request->all();
                    $dto = UpdateUserRequestDto::fromArray($data);
                    $dto->id = $id;

                    $errors = $this->validator->validate($dto);
                    if (count($errors) > 0) {
                        $this->logger->warning('Validation failed for user update', [
                            'user_id' => $id,
                            'errors' => array_map(fn($e) => [
                                'field' => $e->getPropertyPath(),
                                'message' => $e->getMessage()
                            ], iterator_to_array($errors))
                        ]);

                        foreach ($errors as $error) {
                            $this->addFlash('error', $error->getMessage());
                        }
                    } else {
                        $this->adminService->updateUser($dto);

                        $this->logger->info('User updated successfully', ['user_id' => $id]);

                        $this->addFlash('success', 'Пользователь успешно обновлен');
                        return $this->redirectToRoute('admin_users_index');
                    }
                } catch (\RuntimeException $e) {
                    $this->logger->error('Failed to update user', [
                        'user_id' => $id,
                        'error' => $e->getMessage(),
                        'data' => $data ?? []
                    ]);

                    $this->addFlash('error', $e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->error('Unexpected error during user update', [
                        'user_id' => $id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $this->addFlash('error', 'Произошла непредвиденная ошибка');
                }
            }

            $dto = new UpdateUserRequestDto();
            $dto->username = $user->getUsername();
            $dto->email = $user->getEmail();
            $dto->roles = $user->getRoles();
            $dto->isBlocked = $user->isBlocked();

            return $this->render("admin/users/edit.html.twig", [
                'user' => $user,
                'dto' => $dto,
                'available_roles' => ['ROLE_USER', 'ROLE_ADMIN']
            ]);
        } catch (\RuntimeException $e) {
            $this->logger->error('User not found for editing', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            throw $this->createNotFoundException('Пользователь не найден');
        } catch (\Exception $e) {
            $this->logger->error('Failed to load user for editing', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Ошибка при загрузке пользователя');
            return $this->redirectToRoute('admin_users_index');
        }
    }

    #[Route(path: "/admin/users/{id}/delete", name: "admin_users_delete", methods: ["POST"])]
    public function deleteUser(Request $request, int $id): Response
    {
        try {
            if (!$this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
                $this->logger->warning('Invalid CSRF token for user deletion', ['user_id' => $id]);
                throw new \RuntimeException('Недействительный токен CSRF');
            }

            $dto = new DeleteUserRequestDto();
            $dto->id = $id;

            $this->adminService->deleteUser($dto);

            $this->logger->info('User deleted successfully', ['user_id' => $id]);

            $this->addFlash('success', 'Пользователь успешно удален');
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to delete user', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during user deletion', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Произошла непредвиденная ошибка');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route(path: "/admin/users/{userId}/sessions", name: "admin_user_sessions_index", methods: ["GET"])]
    public function userSessionsIndex(Request $request, int $userId): Response
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = max(1, $request->query->getInt('limit', 20));

            $dto = new GetPaginatedSessionsRequestDto();
            $dto->userId = $userId;
            $dto->page = $page;
            $dto->limit = $limit;

            $result = $this->adminService->getPaginatedUserSessions($dto);

            $this->logger->info('Fetched user sessions', [
                'user_id' => $userId,
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return $this->render("admin/sessions/index.html.twig", [
                'sessions' => $result['sessions'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages'],
                'userId' => $userId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user sessions', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Ошибка при загрузке сессий пользователя');
            return $this->redirectToRoute('admin_users_index');
        }
    }

    #[Route(path: "/admin/sessions/{id}", name: "admin_sessions_view", methods: ["GET"])]
    public function viewSession(int $id): Response
    {
        try {
            $dto = new GetSessionByIdRequestDto();
            $dto->id = $id;

            $session = $this->adminService->getSessionById($dto);

            $this->logger->info('Session viewed', ['session_id' => $id]);

            return $this->render("admin/sessions/view.html.twig", [
                'session' => $session,
            ]);
        } catch (\RuntimeException $e) {
            $this->logger->error('Session not found', [
                'session_id' => $id,
                'error' => $e->getMessage()
            ]);

            throw $this->createNotFoundException('Сессия не найдена');
        } catch (\Exception $e) {
            $this->logger->error('Failed to view session', [
                'session_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Ошибка при загрузке сессии');
            return $this->redirectToRoute('admin_users_index');
        }
    }
}

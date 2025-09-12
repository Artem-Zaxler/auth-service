<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Service\AdminService;
use App\Dto\CreateUserRequestDto;
use App\Dto\UpdateUserRequestDto;
use App\Dto\GetUserByIdRequestDto;
use App\Dto\DeleteUserRequestDto;
use App\Dto\GetPaginatedUsersRequestDto;
use App\Dto\ApiResponseDto;
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
    }

    #[Route(path: "/admin/users/create", name: "admin_users_create", methods: ["GET", "POST"])]
    public function createUser(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $dto = CreateUserRequestDto::fromArray($data);
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                $this->logger->error('Validation failed', [
                    'errors' => array_map(fn($e) => $e->getMessage(), iterator_to_array($errors))
                ]);
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                try {
                    $this->adminService->createUser($dto);
                    $this->addFlash('success', 'Пользователь успешно создан');
                    return $this->redirectToRoute('admin_users_index');
                } catch (\RuntimeException $e) {
                    $this->logger->error('Failed to create user', ['error' => $e->getMessage()]);
                    $this->addFlash('error', $e->getMessage());
                }
            }
        }
        return $this->render("admin/users/create.html.twig", [
            'available_roles' => ['ROLE_USER', 'ROLE_ADMIN']
        ]);
    }

    #[Route(path: "/admin/users/{id}", name: "admin_users_view", methods: ["GET"])]
    public function viewUser(int $id): Response
    {
        $dto = new GetUserByIdRequestDto();
        $dto->id = $id;
        try {
            $user = $this->adminService->getUserById($dto);
            return $this->render("admin/users/view.html.twig", [
                'user' => $user
            ]);
        } catch (\RuntimeException $e) {
            $this->logger->error('User not found', ['error' => $e->getMessage()]);
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route(path: "/admin/users/{id}/edit", name: "admin_users_edit", methods: ["GET", "POST"])]
    public function editUser(Request $request, int $id): Response
    {
        $getUserDto = new GetUserByIdRequestDto();
        $getUserDto->id = $id;
        try {
            $user = $this->adminService->getUserById($getUserDto);
            if ($request->isMethod('POST')) {
                $data = $request->request->all();
                $dto = UpdateUserRequestDto::fromArray($data);
                $dto->id = $id;
                $errors = $this->validator->validate($dto);
                if (count($errors) > 0) {
                    $this->logger->error('Validation failed', [
                        'errors' => array_map(fn($e) => $e->getMessage(), iterator_to_array($errors))
                    ]);
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                } else {
                    try {
                        $this->adminService->updateUser($dto);
                        $this->addFlash('success', 'Пользователь успешно обновлен');
                        return $this->redirectToRoute('admin_users_index');
                    } catch (\RuntimeException $e) {
                        $this->logger->error('Failed to update user', ['error' => $e->getMessage()]);
                        $this->addFlash('error', $e->getMessage());
                    }
                }
            } else {
                $dto = new UpdateUserRequestDto();
                $dto->username = $user->getUsername();
                $dto->email = $user->getEmail();
                $dto->roles = $user->getRoles();
                $dto->isBlocked = $user->isBlocked();
            }
            return $this->render("admin/users/edit.html.twig", [
                'user' => $user,
                'available_roles' => ['ROLE_USER', 'ROLE_ADMIN']
            ]);
        } catch (\RuntimeException $e) {
            $this->logger->error('User not found', ['error' => $e->getMessage()]);
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    #[Route(path: "/admin/users/{id}/delete", name: "admin_users_delete", methods: ["POST"])]
    public function deleteUser(Request $request, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            try {
                $dto = new DeleteUserRequestDto();
                $dto->id = $id;
                $this->adminService->deleteUser($dto);
                $this->addFlash('success', 'Пользователь успешно удален');
            } catch (\RuntimeException $e) {
                $this->logger->error('Failed to delete user', ['error' => $e->getMessage()]);
                $this->addFlash('error', $e->getMessage());
            }
        }
        return $this->redirectToRoute('admin_users_index');
    }
}

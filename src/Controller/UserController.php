<?php

namespace App\Controller;

use App\Service\AdminService;
use App\Dto\GetPaginatedUsersRequestDto;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController extends AbstractController
{
    public function __construct(
        private AdminService $adminService,
        private ValidatorInterface $validator
    ) {}

    #[
        Route(path: "/api/users", name: "api_users_list", methods: ["GET"]),
        OA\Get(
            summary: "Получение списка пользователей с пагинацией",
            description: "Возвращает список пользователей с поддержкой пагинации. Требует аутентификации и прав ROLE_USER или SCOPE_user:read",
            tags: ["Users Management"],
            parameters: [
                new OA\Parameter(
                    name: "page",
                    description: "Номер страницы для пагинации",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", minimum: 1, default: 1)
                ),
                new OA\Parameter(
                    name: "limit",
                    description: "Количество элементов на странице",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 20)
                )
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Успешное получение списка пользователей",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "users",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "username", type: "string", example: "john_doe"),
                                        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER"]),
                                        new OA\Property(property: "isBlocked", type: "boolean", example: false),
                                        new OA\Property(property: "createdAt", type: "string", format: "date-time", example: "2023-01-01T12:00:00+00:00"),
                                        new OA\Property(property: "updatedAt", type: "string", format: "date-time", example: "2023-01-02T15:30:00+00:00")
                                    ]
                                )
                            ),
                            new OA\Property(
                                property: "pagination",
                                properties: [
                                    new OA\Property(property: "page", type: "integer", example: 1),
                                    new OA\Property(property: "limit", type: "integer", example: 20),
                                    new OA\Property(property: "total", type: "integer", example: 150),
                                    new OA\Property(property: "totalPages", type: "integer", example: 8)
                                ],
                                type: "object"
                            )
                        ]
                    )
                ),
                new OA\Response(
                    response: 401,
                    description: "Пользователь не аутентифицирован",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Unauthorized"),
                            new OA\Property(property: "message", type: "string", example: "Authentication required")
                        ]
                    )
                ),
                new OA\Response(
                    response: 403,
                    description: "Недостаточно прав для доступа",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Access Denied"),
                            new OA\Property(property: "message", type: "string", example: "Not authenticated.")
                        ]
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "Неверные параметры запроса",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Bad Request"),
                            new OA\Property(property: "message", type: "string", example: "Invalid pagination parameters")
                        ]
                    )
                )
            ]
        )
    ]
    public function listUsers(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_USER') && !$this->isGranted('SCOPE_user:read')) {
            throw new AccessDeniedException('Not authenticated.');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 20));

        $dto = new GetPaginatedUsersRequestDto();
        $dto->page = $page;
        $dto->limit = $limit;

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Bad Request',
                'message' => 'Invalid pagination parameters',
                'details' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->adminService->getPaginatedUsers($dto);

            $usersArray = array_map(function ($user) {
                return [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'isBlocked' => $user->isBlocked(),
                    'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
                ];
            }, $result['users']);

            return $this->json([
                'users' => $usersArray,
                'pagination' => [
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total' => $result['total'],
                    'totalPages' => $result['totalPages']
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

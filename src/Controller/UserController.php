<?php

namespace App\Controller;

use App\Dto\ApiResponseDto;
use App\Service\AdminService;
use OpenApi\Attributes as OA;
use App\Dto\GetPaginatedUsersRequestDto;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
                        ref: new Model(type: ApiResponseDto::class)
                    )
                ),
                new OA\Response(
                    response: 401,
                    description: "Пользователь не аутентифицирован",
                    content: new OA\JsonContent(
                        ref: new Model(type: ApiResponseDto::class)
                    )
                ),
                new OA\Response(
                    response: 403,
                    description: "Недостаточно прав для доступа",
                    content: new OA\JsonContent(
                        ref: new Model(type: ApiResponseDto::class)
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "Неверные параметры запроса",
                    content: new OA\JsonContent(
                        ref: new Model(type: ApiResponseDto::class)
                    )
                )
            ]
        )
    ]
    public function listUsers(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_USER') && !$this->isGranted('SCOPE_user:read')) {
            return $this->json(
                ApiResponseDto::error('Access Denied', Response::HTTP_FORBIDDEN),
                Response::HTTP_FORBIDDEN
            );
        }
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 20));
        $dto = new GetPaginatedUsersRequestDto();
        $dto->page = $page;
        $dto->limit = $limit;
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ApiResponseDto::error(
                    'Invalid pagination parameters',
                    Response::HTTP_BAD_REQUEST,
                    array_map(fn($error) => [
                        'field' => $error->getPropertyPath(),
                        'message' => $error->getMessage()
                    ], iterator_to_array($errors))
                ),
                Response::HTTP_BAD_REQUEST
            );
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
            return $this->json(
                ApiResponseDto::success([
                    'users' => $usersArray,
                    'pagination' => [
                        'page' => $result['page'],
                        'limit' => $result['limit'],
                        'total' => $result['total'],
                        'totalPages' => $result['totalPages']
                    ]
                ])
            );
        } catch (\Exception $e) {
            return $this->json(
                ApiResponseDto::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Dto\LoginDto;
use App\Service\AuthService;
use App\Dto\ApiResponseDto;
use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;
use App\Dto\UserDtoMapper;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private UserDtoMapper $userDtoMapper,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    #[Route("/user/auth/login", name: "user_auth_login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted("ROLE_USER")) {
            return $this->redirect('/authorize');
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render("user/auth/login.html.twig", [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    #[Route("/user/auth/logout", name: "user_auth_logout")]
    public function logout(): void
    {
        throw new \LogicException('This should never be reached.');
    }

    #[
        Route("/api/auth/login", name: "api_auth_login", methods: ["POST"]),
        OA\Post(
            summary: "Аутентификация пользователя",
            description: "Выполняет вход пользователя в систему и возвращает JWT токен",
            tags: ["Authentication"],
            requestBody: new OA\RequestBody(
                description: "Данные для аутентификации",
                required: true,
                content: new OA\JsonContent(
                    ref: new Model(type: LoginDto::class)
                )
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Успешная аутентификация",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
                            new OA\Property(property: "user", ref: new Model(type: UserDto::class))
                        ]
                    )
                ),
                new OA\Response(
                    response: 401,
                    description: "Ошибка аутентификации",
                    content: new OA\JsonContent(
                        ref: new Model(type: ApiResponseDto::class)
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "Неверные данные запроса",
                    content: new OA\JsonContent(
                        ref: new Model(type: ApiResponseDto::class)
                    )
                )
            ]
        )
    ]
    public function loginApi(Request $request): JsonResponse
    {
        try {
            $data = [
                'username' => $request->request->get('username') ?? $request->toArray()['username'] ?? '',
                'password' => $request->request->get('password') ?? $request->toArray()['password'] ?? '',
            ];
            $loginDto = new LoginDto($data);
            $errors = $this->validator->validate($loginDto);
            if (count($errors) > 0) {
                $this->logger->error('Validation failed', [
                    'errors' => array_map(fn($e) => $e->getMessage(), iterator_to_array($errors))
                ]);
                return $this->json(
                    ApiResponseDto::error(
                        'Validation failed',
                        Response::HTTP_BAD_REQUEST,
                        array_map(fn($e) => [
                            'field' => $e->getPropertyPath(),
                            'message' => $e->getMessage()
                        ], iterator_to_array($errors))
                    ),
                    Response::HTTP_BAD_REQUEST
                );
            }
            $user = $this->authService->authenticate($loginDto);
            return $this->json(
                ApiResponseDto::success([
                    'token' => $user['token'],
                    'user' => $user['user'],
                ])
            );
        } catch (AuthenticationException $e) {
            $this->logger->error('Authentication failed', ['error' => $e->getMessage()]);
            return $this->json(
                ApiResponseDto::error($e->getMessage(), Response::HTTP_UNAUTHORIZED),
                Response::HTTP_UNAUTHORIZED
            );
        } catch (\Exception $e) {
            $this->logger->error('Server error', ['error' => $e->getMessage()]);
            return $this->json(
                ApiResponseDto::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[
        Route(path: '/api/auth/logout', name: 'api_logout', methods: ['POST']),
        OA\Post(
            summary: "Выход из системы",
            description: "Выполняет выход пользователя и инвалидирует refresh token",
            tags: ["Authentication"],
            requestBody: new OA\RequestBody(
                description: "Refresh token для инвалидации",
                required: true,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "refresh_token", type: "string", example: "abc123def456...")
                    ]
                )
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Успешный выход из системы",
                    content: new OA\JsonContent(
                        ref: new Model(type: ApiResponseDto::class)
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "Неверный refresh token",
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
                )
            ]
        )
    ]
    public function logoutApi(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            if (!isset($data['refresh_token'])) {
                return $this->json(
                    ApiResponseDto::error('Refresh token is required', Response::HTTP_BAD_REQUEST),
                    Response::HTTP_BAD_REQUEST
                );
            }
            // $this->refreshTokenService->invalidateRefreshToken($data['refresh_token']);
            return $this->json(
                ApiResponseDto::success(['message' => 'Logout successful'])
            );
        } catch (\Exception $e) {
            $this->logger->error('Logout failed', ['error' => $e->getMessage()]);
            return $this->json(
                ApiResponseDto::error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[
        Route(path: '/api/auth/me', name: 'api_auth_check', methods: ['GET']),
        OA\Get(
            summary: "Получение информации о текущем пользователе",
            description: "Возвращает данные аутентифицированного пользователя",
            tags: ["Authentication"],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Успешное получение данных пользователя",
                    content: new OA\JsonContent(
                        ref: new Model(type: UserDto::class)
                    )
                ),
                new OA\Response(
                    response: 401,
                    description: "Пользователь не аутентифицирован",
                    content: new OA\JsonContent(
                        ref: new Model(type: ApiResponseDto::class)
                    )
                )
            ]
        )
    ]
    public function meApi(Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(
                ApiResponseDto::error('Not authenticated', Response::HTTP_UNAUTHORIZED),
                Response::HTTP_UNAUTHORIZED
            );
        }
        $userDto = $this->userDtoMapper->mapUserToDto($user);
        return $this->json(
            ApiResponseDto::success($userDto)
        );
    }
}

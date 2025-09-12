<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Dto\LoginDto;
use App\Service\AuthService;
use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;
use App\Service\Dto\UserDtoMapper;
use App\Service\RefreshTokenService;
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
        private RefreshTokenService $refreshTokenService,
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
                    properties: [
                        new OA\Property(property: "username", type: "string", example: "user@example.com"),
                        new OA\Property(property: "password", type: "string", example: "password123")
                    ]
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
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Authentication failed"),
                            new OA\Property(property: "message", type: "string", example: "Invalid credentials")
                        ]
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "Неверные данные запроса",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Bad Request"),
                            new OA\Property(property: "message", type: "string", example: "Invalid input data")
                        ]
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
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return $this->json([
                    'error' => 'Validation failed',
                    'messages' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->authService->authenticate($loginDto);

            return $this->json([
                'token' =>  $user['token'],
                'user' =>  $user['user'],
            ], Response::HTTP_OK);
        } catch (AuthenticationException $e) {
            return $this->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                        properties: [
                            new OA\Property(property: "message", type: "string", example: "Logout successful")
                        ]
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "Неверный refresh token",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Invalid token"),
                            new OA\Property(property: "message", type: "string", example: "The provided refresh token is invalid")
                        ]
                    )
                ),
                new OA\Response(
                    response: 401,
                    description: "Пользователь не аутентифицирован",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Unauthorized")
                        ]
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
                return $this->json([
                    'error' => 'Bad Request',
                    'message' => 'Refresh token is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->refreshTokenService->invalidateRefreshToken($data['refresh_token']);

            return $this->json([
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Logout failed',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                    content: new OA\JsonContent(ref: new Model(type: UserDto::class))
                ),
                new OA\Response(
                    response: 401,
                    description: "Пользователь не аутентифицирован",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "error", type: "string", example: "Not authenticated")
                        ]
                    )
                )
            ]
        )
    ]
    public function meApi(Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userDto = $this->userDtoMapper->mapUserToDto($user);

        return $this->json($userDto, Response::HTTP_OK);
    }
}

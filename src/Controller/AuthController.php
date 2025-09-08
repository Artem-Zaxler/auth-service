<?php

namespace App\Controller;

use App\Dto\LoginDto;
use App\Dto\RefreshTokenDto;
use App\Service\AuthService;
use OpenApi\Annotations as OA;
use App\DTO\Auth\LoginRequestDto;
use App\DTO\Auth\LoginResponseDto;
use App\Service\Dto\UserDtoMapper;
use App\DTO\Auth\RefreshRequestDto;
use App\Service\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

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

    #[Route("/api/auth/login", name: "api_auth_login", methods: ["POST"])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = [
                'username' => $request->request->get('username'),
                'password' => $request->request->get('password'),
            ];
            $loginDto = new LoginDto($data);

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
        }
    }

    #[Route(path: '/api/auth/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['refresh_token'])) {
            $this->refreshTokenService->invalidateRefreshToken($data['refresh_token']);
        }

        return $this->json([
            'message' => 'Logout successful'
        ]);
    }

    #[Route(path: '/api/auth/me', name: 'api_auth_check', methods: ['GET'])]
    public function me(Security $security): JsonResponse
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

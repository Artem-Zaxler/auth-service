<?php


namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RefreshTokenService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function validateRefreshToken(string $token): ?User
    {
        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findValidToken($token);

        if (!$refreshToken) {
            return null;
        }

        return $refreshToken->getUser();
    }

    public function invalidateRefreshToken(string $token): void
    {
        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $token]);

        if ($refreshToken) {
            $this->em->remove($refreshToken);
            $this->em->flush();
        }
    }
}

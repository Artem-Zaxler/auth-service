<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "RefreshTokenDto",
    description: "Data for refresh token operations"
)]
class RefreshTokenDto
{
    #[OA\Property(type: "string", example: "abc123def456...")]
    #[Assert\NotBlank]
    public string $refreshToken;

    public function __construct(
        array $data
    ) {
        $this->refreshToken = $data['refresh_token'];
    }
}

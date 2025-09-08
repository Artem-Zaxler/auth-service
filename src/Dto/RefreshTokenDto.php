<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenDto
{
    #[Assert\NotBlank]
    public string $refreshToken;

    public function __construct(
        array $data
    ) {
        $this->refreshToken = $data['refresh_token'];
    }
}

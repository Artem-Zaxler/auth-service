<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LoginDto",
    description: "Data for user login",
    required: ["username", "password"]
)]
class LoginDto
{
    #[OA\Property(type: "string", example: "john_doe")]
    #[Assert\NotBlank]
    public string $username;

    #[OA\Property(type: "string", format: "password", example: "password123")]
    #[Assert\NotBlank]
    public string $password;

    public function __construct(
        array $data
    ) {
        $this->username = $data['username'];
        $this->password = $data['password'];
    }
}

<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateUserRequestDto",
    description: "Data for creating a new user",
    required: ["username", "email", "password"]
)]
class CreateUserRequestDto
{
    #[OA\Property(type: "string", example: "john_doe", minLength: 3, maxLength: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $username;

    #[OA\Property(type: "string", format: "email", example: "john@example.com")]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[OA\Property(type: "string", format: "password", example: "password123", minLength: 6)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    #[OA\Property(type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER"])]
    public array $roles = ['ROLE_USER'];

    #[OA\Property(type: "boolean", example: false)]
    public bool $isBlocked = false;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->username = $data['username'] ?? '';
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->roles = $data['roles'] ?? ['ROLE_USER'];
        $dto->isBlocked = $data['isBlocked'] ?? false;

        return $dto;
    }
}

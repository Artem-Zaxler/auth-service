<?php

namespace App\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateUserRequestDto",
    description: "Data for updating a user"
)]
class UpdateUserRequestDto
{
    #[OA\Property(type: "integer", example: 1)]
    public ?int $id = null;

    #[OA\Property(type: "string", example: "john_doe_updated")]
    public ?string $username = null;

    #[OA\Property(type: "string", format: "email", example: "john_updated@example.com")]
    public ?string $email = null;

    #[OA\Property(type: "string", format: "password", example: "newpassword123")]
    public ?string $password = null;

    #[OA\Property(type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER", "ROLE_ADMIN"])]
    public array $roles = [];

    #[OA\Property(type: "boolean", example: false)]
    public bool $isBlocked = false;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->username = $data['username'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->password = $data['password'] ?? null;
        $dto->roles = $data['roles'] ?? [];
        $dto->isBlocked = isset($data['isBlocked']) && $data['isBlocked'] == '1';

        return $dto;
    }
}

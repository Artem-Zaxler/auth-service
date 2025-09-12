<?php

namespace App\Dto;

use DateTimeInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UserDto",
    description: "User data transfer object"
)]
class UserDto
{
    public function __construct(
        #[OA\Property(type: "integer", example: 1)]
        public string $id,

        #[OA\Property(type: "string", format: "email", example: "john@example.com")]
        public string $email,

        #[OA\Property(type: "string", example: "john_doe")]
        public string $username,

        #[OA\Property(type: "array", items: new OA\Items(type: "string"), example: ["ROLE_USER"])]
        public array $roles,

        #[OA\Property(type: "boolean", example: false)]
        public bool $isBlocked,

        #[OA\Property(type: "string", format: "date-time", example: "2023-01-01T12:00:00+00:00")]
        public DateTimeInterface $createdAt,

        #[OA\Property(type: "string", format: "date-time", example: "2023-01-02T15:30:00+00:00")]
        public DateTimeInterface $updatedAt,
    ) {}
}

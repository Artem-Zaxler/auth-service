<?php

namespace App\Dto;

use DateTimeInterface;

class UserDto
{
    public function __construct(
        public string $id,
        public string $email,
        public string $username,
        public array $roles,
        public bool $isBlocked,
        public DateTimeInterface $createdAt,
        public DateTimeInterface $updatedAt,
    ) {}
}

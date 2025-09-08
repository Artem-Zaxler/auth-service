<?php

namespace App\Dto;

class UpdateUserRequestDto
{
    public ?int $id = null;
    public ?string $username = null;
    public ?string $email = null;
    public ?string $password = null;
    public array $roles = [];
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

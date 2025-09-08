<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $username;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    public array $roles = ['ROLE_USER'];

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

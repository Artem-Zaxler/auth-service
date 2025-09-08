<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoginDto
{
    #[Assert\NotBlank]
    public string $username;

    #[Assert\NotBlank]
    public string $password;

    public function __construct(
        array $data
    ) {
        $this->username = $data['username'];
        $this->password = $data['password'];
    }
}

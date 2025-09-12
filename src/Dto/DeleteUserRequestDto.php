<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteUserRequestDto
{
    #[Assert\NotNull(message: "User ID is required")]
    #[Assert\Positive(message: "User ID must be a positive integer")]
    public int $id;
}

<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "DeleteUserRequestDto",
    description: "Data for deleting a user"
)]
class DeleteUserRequestDto
{
    #[OA\Property(type: "integer", example: 1)]
    #[Assert\NotNull(message: "User ID is required")]
    #[Assert\Positive(message: "User ID must be a positive integer")]
    public int $id;
}

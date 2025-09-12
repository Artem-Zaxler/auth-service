<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GetUserByIdRequestDto",
    description: "Data for getting user by ID"
)]
class GetUserByIdRequestDto
{
    #[OA\Property(type: "integer", example: 1)]
    #[Assert\NotNull(message: "User ID is required")]
    #[Assert\Positive(message: "User ID must be a positive integer")]
    public int $id;
}

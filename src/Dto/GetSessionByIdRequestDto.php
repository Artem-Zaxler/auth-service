<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GetSessionByIdRequestDto",
    description: "Data for getting session by ID"
)]
class GetSessionByIdRequestDto
{
    #[OA\Property(type: "integer", example: 1)]
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $id;

    public static function fromArray(?array $data): self
    {
        $dto = new self();
        $dto->id = $data['id'] ?? 0;
        return $dto;
    }
}

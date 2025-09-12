<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GetPaginatedUsersRequestDto",
    description: "Data for getting paginated users"
)]
class GetPaginatedUsersRequestDto
{
    #[OA\Property(type: "integer", example: 1, minimum: 1)]
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $page;

    #[OA\Property(type: "integer", example: 20, minimum: 1, maximum: 100)]
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $limit;

    public static function fromArray(?array $data): GetPaginatedUsersRequestDto
    {
        $dto = new self();
        $dto->page = $data['page'] ?? 1;
        $dto->limit = $data['limit'] ?? 20;
        return $dto;
    }
}

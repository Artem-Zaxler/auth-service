<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GetPaginatedSessionsRequestDto",
    description: "Data for getting paginated user sessions"
)]
class GetPaginatedSessionsRequestDto
{
    #[OA\Property(type: "integer", example: 1)]
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $userId;

    #[OA\Property(type: "integer", example: 1, minimum: 1)]
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $page;

    #[OA\Property(type: "integer", example: 20, minimum: 1, maximum: 100)]
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $limit;

    public static function fromArray(?array $data): self
    {
        $dto = new self();
        $dto->userId = $data['userId'] ?? 0;
        $dto->page = $data['page'] ?? 1;
        $dto->limit = $data['limit'] ?? 20;
        return $dto;
    }
}

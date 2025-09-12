<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class GetPaginatedSessionsRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $userId;

    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $page;

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

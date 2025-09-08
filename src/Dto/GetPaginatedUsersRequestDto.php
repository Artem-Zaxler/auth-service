<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class GetPaginatedUsersRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Type(type: "integer")]
    public int $page;

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

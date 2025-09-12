<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class GetSessionByIdRequestDto
{
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

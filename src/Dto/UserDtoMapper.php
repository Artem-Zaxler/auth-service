<?php

namespace App\Dto;

use App\Dto\UserDto;
use App\Entity\User;

class UserDtoMapper
{
    public function mapUserToDto(User $user): UserDto
    {
        return new UserDto(
            $user->getId(),
            $user->getEmail(),
            $user->getUsername(),
            $user->getRoles(),
            $user->isBlocked(),
            $user->getCreatedAt(),
            $user->getUpdatedAt(),
        );
    }

    public function mapUsersToDtoArray(array $users): array
    {
        return array_map([$this, 'mapUserToDto'], $users);
    }
}

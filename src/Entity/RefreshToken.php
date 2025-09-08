<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $userEntity = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private bool $isRevoked = false;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getToken(): ?string
    {
        return $this->token;
    }
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }
    public function getUserEntity(): ?User
    {
        return $this->userEntity;
    }
    public function setUserEntity(?User $userEntity): self
    {
        $this->userEntity = $userEntity;
        return $this;
    }
    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    public function isRevoked(): bool
    {
        return $this->isRevoked;
    }
    public function setIsRevoked(bool $isRevoked): self
    {
        $this->isRevoked = $isRevoked;
        return $this;
    }
}

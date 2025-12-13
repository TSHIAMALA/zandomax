<?php

namespace App\Entity;

use App\Repository\SpaceTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SpaceTypeRepository::class)]
#[ORM\Table(name: 'space_type')]
#[ApiResource]
class SpaceType
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['space:read'])]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    #[Groups(['space:read'])]
    private ?string $label = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBinary();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }
}

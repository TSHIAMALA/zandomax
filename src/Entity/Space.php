<?php

namespace App\Entity;

use App\Enum\SpaceStatus;
use App\Repository\SpaceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SpaceRepository::class)]
#[ORM\Table(name: 'spaces')]
#[ApiResource]
class Space
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['space:read'])]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: SpaceCategory::class)]
    #[ORM\JoinColumn(name: 'space_category_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['space:read'])]
    private ?SpaceCategory $spaceCategory = null;

    #[ORM\ManyToOne(targetEntity: SpaceType::class)]
    #[ORM\JoinColumn(name: 'space_type_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['space:read'])]
    private ?SpaceType $spaceType = null;

    #[ORM\Column(length: 100)]
    #[Groups(['space:read'])]
    private ?string $zone = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $architectureCoord = null;

    #[ORM\Column(length: 50, enumType: SpaceStatus::class)]
    #[Groups(['space:read'])]
    private ?SpaceStatus $status = SpaceStatus::AVAILABLE;

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

    public function getSpaceCategory(): ?SpaceCategory
    {
        return $this->spaceCategory;
    }

    public function setSpaceCategory(?SpaceCategory $spaceCategory): static
    {
        $this->spaceCategory = $spaceCategory;

        return $this;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(string $zone): static
    {
        $this->zone = $zone;

        return $this;
    }

    public function getArchitectureCoord(): ?array
    {
        return $this->architectureCoord;
    }

    public function setArchitectureCoord(?array $architectureCoord): static
    {
        $this->architectureCoord = $architectureCoord;

        return $this;
    }

    public function getStatus(): ?SpaceStatus
    {
        return $this->status;
    }

    public function setStatus(SpaceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSpaceType(): ?SpaceType
    {
        return $this->spaceType;
    }

    public function setSpaceType(?SpaceType $spaceType): static
    {
        $this->spaceType = $spaceType;

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

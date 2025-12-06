<?php

namespace App\Entity;

use App\Repository\SpaceCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SpaceCategoryRepository::class)]
#[ORM\Table(name: 'space_category')]
#[ApiResource]
class SpaceCategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['space:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBinary();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
}

<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
#[ORM\Table(name: 'videos')]
#[ApiResource]
class Video
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Space::class)]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['video:read'])]
    private ?Space $space = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['video:read'])]
    private ?string $filepath = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['video:read'])]
    private ?string $thumbnail = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    #[Groups(['video:read'])]
    private ?string $sizeBytes = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['video:read'])]
    private ?\DateTimeInterface $recordedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBinary();
        $this->recordedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSpace(): ?Space
    {
        return $this->space;
    }

    public function setSpace(?Space $space): static
    {
        $this->space = $space;

        return $this;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(string $filepath): static
    {
        $this->filepath = $filepath;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getSizeBytes(): ?string
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(?string $sizeBytes): static
    {
        $this->sizeBytes = $sizeBytes;

        return $this;
    }

    public function getRecordedAt(): ?\DateTimeInterface
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(\DateTimeInterface $recordedAt): static
    {
        $this->recordedAt = $recordedAt;

        return $this;
    }
}

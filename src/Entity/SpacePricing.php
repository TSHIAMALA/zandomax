<?php

namespace App\Entity;

use App\Enum\Periodicity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'space_pricing')]
class SpacePricing
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Space::class)]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id', nullable: false)]
    private ?Space $space = null;

    #[ORM\Column(length: 50, enumType: Periodicity::class)]
    private ?Periodicity $periodicity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $amount = null;

    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(name: 'currency_id', referencedColumnName: 'id', nullable: true)]
    private ?Currency $currency = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minDuration = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxDuration = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBinary();
        $this->createdAt = new \DateTime();
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

    public function getPeriodicity(): ?Periodicity
    {
        return $this->periodicity;
    }

    public function setPeriodicity(Periodicity $periodicity): static
    {
        $this->periodicity = $periodicity;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): static
    {
        $this->currency = $currency;
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

    public function getMinDuration(): ?int
    {
        return $this->minDuration;
    }

    public function setMinDuration(?int $minDuration): static
    {
        $this->minDuration = $minDuration;
        return $this;
    }

    public function getMaxDuration(): ?int
    {
        return $this->maxDuration;
    }

    public function setMaxDuration(?int $maxDuration): static
    {
        $this->maxDuration = $maxDuration;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

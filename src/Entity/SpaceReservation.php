<?php

namespace App\Entity;

use App\Enum\Periodicity;
use App\Enum\ReservationStatus;
use App\Repository\SpaceReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SpaceReservationRepository::class)]
#[ORM\Table(name: 'space_reservation')]
#[ApiResource]
class SpaceReservation
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    #[ORM\JoinColumn(name: 'merchant_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['reservation:read'])]
    private ?Merchant $merchant = null;

    #[ORM\ManyToOne(targetEntity: Space::class)]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['reservation:read'])]
    private ?Space $space = null;

    #[ORM\Column(length: 50, enumType: Periodicity::class)]
    #[Groups(['reservation:read'])]
    private ?Periodicity $periodicity = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['reservation:read'])]
    private ?int $duration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Groups(['reservation:read'])]
    private ?string $firstPaymentAmount = null;

    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(name: 'currency_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['reservation:read'])]
    private ?Currency $currency = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    #[ORM\Column(length: 50, enumType: ReservationStatus::class)]
    #[Groups(['reservation:read'])]
    private ?ReservationStatus $status = ReservationStatus::PENDING_ADMIN;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['reservation:read'])]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    #[Groups(['reservation:read'])]
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

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): static
    {
        $this->merchant = $merchant;
        return $this;
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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getFirstPaymentAmount(): ?string
    {
        return $this->firstPaymentAmount;
    }

    public function setFirstPaymentAmount(?string $firstPaymentAmount): static
    {
        $this->firstPaymentAmount = $firstPaymentAmount;
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

    public function getStatus(): ?ReservationStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
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

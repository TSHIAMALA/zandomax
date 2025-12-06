<?php

namespace App\Entity;

use App\Enum\BillingCycle;
use App\Enum\ContractStatus;
use App\Repository\ContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: 'contracts')]
#[ApiResource]
class Contract
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Merchant::class)]
    #[ORM\JoinColumn(name: 'merchant_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['contract:read'])]
    private ?Merchant $merchant = null;

    #[ORM\ManyToOne(targetEntity: Space::class)]
    #[ORM\JoinColumn(name: 'space_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['contract:read'])]
    private ?Space $space = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['contract:read'])]
    private ?string $contractCode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['contract:read'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['contract:read'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['contract:read'])]
    private ?string $rentAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['contract:read'])]
    private ?string $guaranteeAmount = null;

    #[ORM\Column(length: 50, enumType: BillingCycle::class)]
    #[Groups(['contract:read'])]
    private ?BillingCycle $billingCycle = BillingCycle::MONTHLY;

    #[ORM\Column(length: 50, enumType: ContractStatus::class)]
    #[Groups(['contract:read'])]
    private ?ContractStatus $status = ContractStatus::PENDING_SIGNATURE;

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

    public function getContractCode(): ?string
    {
        return $this->contractCode;
    }

    public function setContractCode(string $contractCode): static
    {
        $this->contractCode = $contractCode;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getRentAmount(): ?string
    {
        return $this->rentAmount;
    }

    public function setRentAmount(string $rentAmount): static
    {
        $this->rentAmount = $rentAmount;

        return $this;
    }

    public function getGuaranteeAmount(): ?string
    {
        return $this->guaranteeAmount;
    }

    public function setGuaranteeAmount(string $guaranteeAmount): static
    {
        $this->guaranteeAmount = $guaranteeAmount;

        return $this;
    }

    public function getBillingCycle(): ?BillingCycle
    {
        return $this->billingCycle;
    }

    public function setBillingCycle(BillingCycle $billingCycle): static
    {
        $this->billingCycle = $billingCycle;

        return $this;
    }

    public function getStatus(): ?ContractStatus
    {
        return $this->status;
    }

    public function setStatus(ContractStatus $status): static
    {
        $this->status = $status;

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

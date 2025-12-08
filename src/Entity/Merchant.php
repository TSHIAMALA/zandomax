<?php

namespace App\Entity;

use App\Enum\KycLevel;
use App\Enum\MerchantStatus;
use App\Enum\PersonType;
use App\Repository\MerchantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MerchantRepository::class)]
#[ORM\Table(name: 'merchants')]
#[ApiResource]
class Merchant
{
    #[ORM\Id]
    #[ORM\Column(type: 'binary', length: 16)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: MerchantCategory::class)]
    #[ORM\JoinColumn(name: 'merchant_category_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['merchant:read'])]
    private ?MerchantCategory $merchantCategory = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $biometricHash = null;

    #[ORM\Column(length: 100)]
    #[Groups(['merchant:read'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 100)]
    #[Groups(['merchant:read'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['merchant:read'])]
    private ?string $phone = null;

    #[ORM\Column(length: 150, unique: true, nullable: true)]
    #[Groups(['merchant:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $accountNumber = null;

    #[ORM\Column(length: 50, enumType: MerchantStatus::class)]
    #[Groups(['merchant:read'])]
    private ?MerchantStatus $status = MerchantStatus::PENDING_VALIDATION;

    #[ORM\Column(length: 50, enumType: KycLevel::class)]
    private ?KycLevel $kycLevel = KycLevel::BASIC;

    #[ORM\Column(length: 50, enumType: PersonType::class, nullable: true)]
    #[Groups(['merchant:read'])]
    private ?PersonType $personType = PersonType::PHYSICAL;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $documents = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP', 'onUpdate' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toBinary();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMerchantCategory(): ?MerchantCategory
    {
        return $this->merchantCategory;
    }

    public function setMerchantCategory(?MerchantCategory $merchantCategory): static
    {
        $this->merchantCategory = $merchantCategory;

        return $this;
    }

    public function getBiometricHash(): ?string
    {
        return $this->biometricHash;
    }

    public function setBiometricHash(string $biometricHash): static
    {
        $this->biometricHash = $biometricHash;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): static
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    public function getStatus(): ?MerchantStatus
    {
        return $this->status;
    }

    public function setStatus(MerchantStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getKycLevel(): ?KycLevel
    {
        return $this->kycLevel;
    }

    public function setKycLevel(KycLevel $kycLevel): static
    {
        $this->kycLevel = $kycLevel;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPersonType(): ?PersonType
    {
        return $this->personType;
    }

    public function setPersonType(?PersonType $personType): static
    {
        $this->personType = $personType;

        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): static
    {
        $this->documents = $documents;

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

<?php

namespace App\Service;

use App\Entity\Merchant;
use App\Entity\MerchantCategory;
use App\Entity\User;
use App\Enum\KycLevel;
use App\Enum\MerchantStatus;
use App\Repository\MerchantCategoryRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MerchantRegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private RoleRepository $roleRepository,
        private MerchantCategoryRepository $merchantCategoryRepository
    ) {
    }

    public function registerMerchant(array $data): Merchant
    {
        // 1. Create Merchant
        $merchant = new Merchant();
        $merchant->setFirstname($data['firstname']);
        $merchant->setLastname($data['lastname']);
        $merchant->setPhone($data['phone']);
        $merchant->setEmail($data['email']);
        $merchant->setBiometricHash($data['biometric_hash'] ?? 'pending_' . uniqid());
        $merchant->setAccountNumber($data['account_number'] ?? null);
        $merchant->setStatus(MerchantStatus::PENDING_VALIDATION);
        $merchant->setKycLevel(KycLevel::BASIC);

        $category = $this->merchantCategoryRepository->find($data['category_id']);
        if (!$category) {
            throw new \InvalidArgumentException('Invalid merchant category');
        }
        $merchant->setMerchantCategory($category);

        $this->entityManager->persist($merchant);

        // 2. Create User account for Merchant
        $user = new User();
        $user->setEmail($data['email']);
        $user->setUsername($data['username'] ?? $data['email']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );
        $user->setMerchant($merchant);
        
        $merchantRole = $this->roleRepository->findOneBy(['code' => 'ROLE_MERCHANT']);
        if ($merchantRole) {
            $user->addUserRole($merchantRole);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $merchant;
    }
}

<?php

namespace App\Command;

use App\Entity\Currency;
use App\Entity\Merchant;
use App\Entity\MerchantCategory;
use App\Entity\PricingRule;
use App\Entity\Role;
use App\Entity\Space;
use App\Entity\SpaceCategory;
use App\Entity\SpaceType;
use App\Entity\User;
use App\Enum\KycLevel;
use App\Enum\MerchantStatus;
use App\Enum\Periodicity;
use App\Enum\SpaceStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Creates test data for reservation flow verification',
)]
class CreateTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Create Role Merchant if not exists
        $roleMerchant = $this->entityManager->getRepository(Role::class)->findOneBy(['code' => 'ROLE_MERCHANT']);
        if (!$roleMerchant) {
            $roleMerchant = new Role();
            $roleMerchant->setCode('ROLE_MERCHANT');
            $roleMerchant->setLabel('Marchand');
            $this->entityManager->persist($roleMerchant);
        }

        // 2. Create Merchant Category
        $category = $this->entityManager->getRepository(MerchantCategory::class)->findOneBy(['name' => 'Test Category']);
        if (!$category) {
            $category = new MerchantCategory();
            $category->setName('Test Category');
            $this->entityManager->persist($category);
        }

        // 3. Create Merchant
        $merchant = $this->entityManager->getRepository(Merchant::class)->findOneBy(['email' => 'test@merchant.com']);
        if (!$merchant) {
            $merchant = new Merchant();
            $merchant->setFirstname('Test');
            $merchant->setLastname('Merchant');
            $merchant->setEmail('test@merchant.com');
            $merchant->setPhone('0000000000');
            $merchant->setMerchantCategory($category);
            $merchant->setStatus(MerchantStatus::ACTIVE);
            $merchant->setKycLevel(KycLevel::VERIFIED);
            $merchant->setBiometricHash('test_hash_' . uniqid());
            $this->entityManager->persist($merchant);
        }

        // 4. Create User for Merchant
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'merchant_test']);
        if (!$user) {
            $user = new User();
            $user->setUsername('merchant_test');
            $user->setEmail('test@merchant.com');
            $user->setMerchant($merchant);
            $user->addUserRole($roleMerchant);
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
            $user->setPassword($hashedPassword);
            $this->entityManager->persist($user);
        }

        // 5. Create Space Type & Category
        $spaceType = $this->entityManager->getRepository(SpaceType::class)->findOneBy(['code' => 'KIOSK']);
        if (!$spaceType) {
            $spaceType = new SpaceType();
            $spaceType->setCode('KIOSK');
            $spaceType->setLabel('Kiosque');
            $this->entityManager->persist($spaceType);
        }

        $spaceCategory = $this->entityManager->getRepository(SpaceCategory::class)->findOneBy(['name' => 'Standard']);
        if (!$spaceCategory) {
            $spaceCategory = new SpaceCategory();
            $spaceCategory->setName('Standard');
            $this->entityManager->persist($spaceCategory);
        }

        // 6. Create Space
        $space = $this->entityManager->getRepository(Space::class)->findOneBy(['code' => 'SPACE-TEST-001']);
        if (!$space) {
            $space = new Space();
            $space->setCode('SPACE-TEST-001');
            $space->setSpaceType($spaceType);
            $space->setSpaceCategory($spaceCategory);
            $space->setZone('Zone A');
            $space->setStatus(SpaceStatus::AVAILABLE);
            $this->entityManager->persist($space);
        }

        // 7. Create Currency
        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']);
        if (!$currency) {
            $currency = new Currency();
            $currency->setCode('USD');
            $currency->setLabel('US Dollar');
            $currency->setSymbol('$');
            $this->entityManager->persist($currency);
        }

        // 8. Create Pricing Rule
        $pricingRule = $this->entityManager->getRepository(PricingRule::class)->findOneBy(['space' => $space]);
        if (!$pricingRule) {
            $pricingRule = new PricingRule();
            $pricingRule->setSpace($space);
            $pricingRule->setPrice('500.00');
            $pricingRule->setCurrency($currency);
            $pricingRule->setPeriodicity(Periodicity::MONTH);
            $pricingRule->setMinDuration(1);
            $this->entityManager->persist($pricingRule);
        }

        $this->entityManager->flush();

        $output->writeln('Test data created successfully.');
        $output->writeln('User: merchant_test / password');
        $output->writeln('Space: SPACE-TEST-001');

        return Command::SUCCESS;
    }
}

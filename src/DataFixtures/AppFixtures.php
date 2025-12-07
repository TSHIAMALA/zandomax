<?php

namespace App\DataFixtures;

use App\Entity\{User, Role, Merchant, MerchantCategory, Space, SpaceCategory, Contract, Payment, Video, AuditLog, Currency, SpaceType, PricingRule, SpaceReservation};
use App\Enum\{MerchantStatus, KycLevel, SpaceStatus, ContractStatus, BillingCycle, PaymentStatus, PaymentType, PersonType, Periodicity, ReservationStatus};
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // ROLES
        $roles = [
            'ROLE_SUPER_ADMIN' => ['Super administrateur', 'Accès total au système'],
            'ROLE_MARKET_ADMIN' => ['Administrateur du marché', 'Gestion du marché et des marchands'],
            'ROLE_CASHIER' => ['Caissier', 'Gestion des paiements au guichet'],
            'ROLE_ENROLLMENT_AGENT' => ['Agent enrôlement', 'Enrôlement biométrique sur site'],
            'ROLE_CONTROLLER' => ['Contrôleur', 'Vérification terrain et signalement dommages'],
            'ROLE_MERCHANT' => ['Marchand', 'Accès portail marchand'],
        ];

        $roleEntities = [];
        foreach ($roles as $code => $data) {
            $role = new Role();
            $role->setCode($code);
            $role->setLabel($data[0]);
            $role->setDescription($data[1]);
            $manager->persist($role);
            $roleEntities[$code] = $role;
        }

        // SPACE CATEGORIES
        $spaceCategories = [
            'Boutique' => 'Boutiques fermées dans le bâtiment principal',
            'Étal' => 'Étal ouvert dans les allées du marché',
            'Kiosque' => 'Kiosques autonomes',
            'Entrepôt' => 'Espace de stockage et entrepôts',
        ];

        $spaceCategoryEntities = [];
        foreach ($spaceCategories as $name => $desc) {
            $cat = new SpaceCategory();
            $cat->setName($name);
            $cat->setDescription($desc);
            $manager->persist($cat);
            $spaceCategoryEntities[$name] = $cat;
        }

        // CURRENCIES
        $currencies = [
            'CDF' => ['Franc Congolais', 'FC'],
            'USD' => ['Dollar Américain', '$'],
        ];

        $currencyEntities = [];
        foreach ($currencies as $code => $data) {
            $currency = new Currency();
            $currency->setCode($code);
            $currency->setLabel($data[0]);
            $currency->setSymbol($data[1]);
            $manager->persist($currency);
            $currencyEntities[$code] = $currency;
        }

        // SPACE TYPES
        $spaceTypes = [
            'LOCAL' => ['Local commercial', 'Local fermé'],
            'ETAL' => ['Étalage', 'Étal ouvert'],
            'BOX' => ['Box', 'Petit espace fermé'],
            'DEPOT' => ['Dépôt', 'Espace d\'entreposage'],
        ];

        $spaceTypeEntities = [];
        foreach ($spaceTypes as $code => $data) {
            $spaceType = new SpaceType();
            $spaceType->setCode($code);
            $spaceType->setLabel($data[0]);
            $spaceType->setDescription($data[1]);
            $manager->persist($spaceType);
            $spaceTypeEntities[$code] = $spaceType;
        }

        // MERCHANT CATEGORIES
        $merchantCategories = [
            'Alimentaire' => 'Produits alimentaires',
            'Textile' => 'Textile & habillement',
            'Électronique' => 'Électronique & accessoires',
        ];

        $merchantCategoryEntities = [];
        foreach ($merchantCategories as $name => $desc) {
            $cat = new MerchantCategory();
            $cat->setName($name);
            $cat->setDescription($desc);
            $manager->persist($cat);
            $merchantCategoryEntities[$name] = $cat;
        }

        // MERCHANTS
        $merchantJean = new Merchant();
        $merchantJean->setMerchantCategory($merchantCategoryEntities['Alimentaire']);
        $merchantJean->setBiometricHash('biohash_001');
        $merchantJean->setFirstname('Jean');
        $merchantJean->setLastname('Kasongo');
        $merchantJean->setPhone('243810000001');
        $merchantJean->setEmail('jean.kasongo@example.com');
        $merchantJean->setAccountNumber('ACC001-BCD');
        $merchantJean->setStatus(MerchantStatus::ACTIVE);
        $merchantJean->setKycLevel(KycLevel::FULL);
        $manager->persist($merchantJean);

        $merchantMarie = new Merchant();
        $merchantMarie->setMerchantCategory($merchantCategoryEntities['Textile']);
        $merchantMarie->setBiometricHash('biohash_002');
        $merchantMarie->setFirstname('Marie');
        $merchantMarie->setLastname('Mukendi');
        $merchantMarie->setPhone('243810000002');
        $merchantMarie->setEmail('marie.mukendi@example.com');
        $merchantMarie->setAccountNumber('ACC002-BCD');
        $merchantMarie->setStatus(MerchantStatus::PENDING_VALIDATION);
        $merchantMarie->setKycLevel(KycLevel::BASIC);
        $manager->persist($merchantMarie);

        // USERS
        $superAdmin = new User();
        $superAdmin->setEmail('superadmin@zando.local');
        $superAdmin->setUsername('superadmin');
        $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, 'superadmin'));
        $superAdmin->addUserRole($roleEntities['ROLE_SUPER_ADMIN']);
        $manager->persist($superAdmin);

        $marketAdmin = new User();
        $marketAdmin->setEmail('adminmarche@zando.local');
        $marketAdmin->setUsername('adminmarche');
        $marketAdmin->setPassword($this->passwordHasher->hashPassword($marketAdmin, 'adminmarche'));
        $marketAdmin->addUserRole($roleEntities['ROLE_MARKET_ADMIN']);
        $manager->persist($marketAdmin);

        $merchantUser = new User();
        $merchantUser->setEmail('jean.kasongo@example.com');
        $merchantUser->setUsername('jean.marchand');
        $merchantUser->setPassword($this->passwordHasher->hashPassword($merchantUser, 'merchant'));
        $merchantUser->setMerchant($merchantJean);
        $merchantUser->addUserRole($roleEntities['ROLE_MERCHANT']);
        $manager->persist($merchantUser);

        // SPACES
        $space1 = new Space();
        $space1->setCode('B-001');
        $space1->setSpaceCategory($spaceCategoryEntities['Boutique']);
        $space1->setSpaceType($spaceTypeEntities['LOCAL']);
        $space1->setZone('Zone A');
        $space1->setArchitectureCoord(['x' => 10, 'y' => 20]);
        $space1->setStatus(SpaceStatus::OCCUPIED);
        $manager->persist($space1);

        $space2 = new Space();
        $space2->setCode('B-002');
        $space2->setSpaceCategory($spaceCategoryEntities['Boutique']);
        $space2->setSpaceType($spaceTypeEntities['LOCAL']);
        $space2->setZone('Zone A');
        $space2->setArchitectureCoord(['x' => 15, 'y' => 20]);
        $space2->setStatus(SpaceStatus::AVAILABLE);
        $manager->persist($space2);

        $space3 = new Space();
        $space3->setCode('E-010');
        $space3->setSpaceCategory($spaceCategoryEntities['Étal']);
        $space3->setSpaceType($spaceTypeEntities['ETAL']);
        $space3->setZone('Zone B');
        $space3->setArchitectureCoord(['x' => 5, 'y' => 30]);
        $space3->setStatus(SpaceStatus::AVAILABLE);
        $manager->persist($space3);

        // PRICING RULES
        $pricingRules = [
            ['space' => $space1, 'periodicity' => Periodicity::MONTH, 'price' => '300.00'],
            ['space' => $space1, 'periodicity' => Periodicity::QUARTER, 'price' => '850.00'],
            ['space' => $space1, 'periodicity' => Periodicity::YEAR, 'price' => '3200.00'],
            ['space' => $space2, 'periodicity' => Periodicity::MONTH, 'price' => '300.00'],
            ['space' => $space3, 'periodicity' => Periodicity::WEEK, 'price' => '50.00'],
            ['space' => $space3, 'periodicity' => Periodicity::DAY, 'price' => '10.00'],
        ];

        foreach ($pricingRules as $ruleData) {
            $rule = new PricingRule();
            $rule->setSpace($ruleData['space']);
            $rule->setPeriodicity($ruleData['periodicity']);
            $rule->setPrice($ruleData['price']);
            $rule->setCurrency($currencyEntities['CDF']);
            $manager->persist($rule);
        }

        // CONTRACTS
        $contract = new Contract();
        $contract->setMerchant($merchantJean);
        $contract->setSpace($space1);
        $contract->setContractCode('CTR-2025-0001');
        $contract->setStartDate(new \DateTime('2025-01-01'));
        $contract->setEndDate(new \DateTime('2025-12-31'));
        $contract->setRentAmount('300.00');
        $contract->setGuaranteeAmount('600.00');
        $contract->setBillingCycle(BillingCycle::MONTHLY);
        $contract->setStatus(ContractStatus::ACTIVE);
        $manager->persist($contract);

        // PAYMENTS
        $payment1 = new Payment();
        $payment1->setMerchant($merchantJean);
        $payment1->setContract($contract);
        $payment1->setInitiatedBy($merchantUser);
        $payment1->setProcessedBy($marketAdmin);
        $payment1->setType(PaymentType::LOYER);
        $payment1->setAmount('300.00');
        $payment1->setDueDate(new \DateTime('2025-01-05'));
        $payment1->setPaymentDate(new \DateTime('2025-01-04 10:30:00'));
        $payment1->setBankingTransactionId('TXN-LO-2025-0001');
        $payment1->setStatus(PaymentStatus::PAID);
        $manager->persist($payment1);

        $payment2 = new Payment();
        $payment2->setMerchant($merchantJean);
        $payment2->setContract($contract);
        $payment2->setInitiatedBy($merchantUser);
        $payment2->setType(PaymentType::LOYER);
        $payment2->setAmount('300.00');
        $payment2->setDueDate(new \DateTime('2025-02-05'));
        $payment2->setStatus(PaymentStatus::PENDING);
        $manager->persist($payment2);

        // SPACE RESERVATIONS (for testing)
        $reservation1 = new SpaceReservation();
        $reservation1->setMerchant($merchantJean);
        $reservation1->setSpace($space2); // B-002 is available
        $reservation1->setPeriodicity(Periodicity::MONTH);
        $reservation1->setDuration(6);
        $reservation1->setFirstPaymentAmount('1800.00'); // 300 * 6
        $reservation1->setCurrency($currencyEntities['CDF']);
        $reservation1->setStatus(ReservationStatus::PENDING_ADMIN);
        $manager->persist($reservation1);

        $reservation2 = new SpaceReservation();
        $reservation2->setMerchant($merchantJean);
        $reservation2->setSpace($space3); // E-010 is available
        $reservation2->setPeriodicity(Periodicity::WEEK);
        $reservation2->setDuration(4);
        $reservation2->setFirstPaymentAmount('200.00'); // 50 * 4
        $reservation2->setCurrency($currencyEntities['CDF']);
        $reservation2->setStatus(ReservationStatus::PENDING_ADMIN);
        $manager->persist($reservation2);

        // VIDEOS
        $video = new Video();
        $video->setSpace($space1);
        $video->setFilepath('/videos/zoneA/B-001/2025-01-01-090000.mp4');
        $video->setThumbnail('/videos/zoneA/B-001/thumb-2025-01-01-090000.jpg');
        $video->setSizeBytes('1048576');
        $video->setRecordedAt(new \DateTime('2025-01-01 09:00:00'));
        $manager->persist($video);

        // AUDIT LOGS
        $audit1 = new AuditLog();
        $audit1->setActor('system');
        $audit1->setAction('INIT_DB');
        $audit1->setModule('system');
        $audit1->setPayload(['message' => 'Initialisation base avec seed']);
        $manager->persist($audit1);

        $audit2 = new AuditLog();
        $audit2->setActor('adminmarche');
        $audit2->setAction('CREATE_CONTRACT');
        $audit2->setModule('contracts');
        $audit2->setPayload(['contract_code' => 'CTR-2025-0001']);
        $manager->persist($audit2);

        $manager->flush();
    }
}

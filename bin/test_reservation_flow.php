<?php

use App\Entity\Contract;
use App\Entity\Merchant;
use App\Entity\Space;
use App\Enum\Periodicity;
use App\Kernel;
use App\Service\SpaceReservationService;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine')->getManager();
$reservationService = $container->get(SpaceReservationService::class);

echo "Starting Reservation Flow Test...\n";

// 1. Get Test Data
$merchant = $entityManager->getRepository(Merchant::class)->findOneBy(['email' => 'test@merchant.com']);
$space = $entityManager->getRepository(Space::class)->findOneBy(['code' => 'SPACE-TEST-001']);

if (!$merchant || !$space) {
    die("Error: Test data not found. Run 'php bin/console app:create-test-data' first.\n");
}

echo "Merchant: " . $merchant->getFirstname() . "\n";
echo "Space: " . $space->getCode() . "\n";

// 2. Create Reservation
echo "Creating reservation...\n";
try {
    $reservation = $reservationService->createReservation(
        $merchant,
        $space,
        Periodicity::MONTH,
        1
    );
    echo "Reservation created. ID: " . $reservation->getId() . "\n";
} catch (\Exception $e) {
    die("Error creating reservation: " . $e->getMessage() . "\n");
}

// 3. Approve Reservation
echo "Approving reservation...\n";
try {
    $reservationService->approveReservation($reservation);
    echo "Reservation approved.\n";
} catch (\Exception $e) {
    die("Error approving reservation: " . $e->getMessage() . "\n");
}

// 4. Verify Contract
echo "Verifying contract generation...\n";
$contract = $entityManager->getRepository(Contract::class)->findOneBy([
    'merchant' => $merchant,
    'space' => $space
], ['createdAt' => 'DESC']);

if ($contract) {
    echo "SUCCESS: Contract found!\n";
    echo "Contract Code: " . $contract->getContractCode() . "\n";
    echo "Rent Amount: " . $contract->getRentAmount() . "\n";
    echo "Status: " . $contract->getStatus()->value . "\n";
} else {
    echo "FAILURE: No contract found.\n";
    exit(1);
}

// Cleanup (Optional)
// $entityManager->remove($contract);
// $entityManager->remove($reservation);
// $entityManager->flush();

echo "Test completed successfully.\n";

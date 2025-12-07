<?php

namespace App\Service;

use App\Entity\Merchant;
use App\Entity\Space;
use App\Entity\SpaceReservation;
use App\Enum\Periodicity;
use App\Enum\ReservationStatus;
use App\Enum\SpaceStatus;
use App\Repository\CurrencyRepository;
use App\Repository\SpaceReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

class SpaceReservationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpaceReservationRepository $reservationRepository,
        private PricingService $pricingService,
        private CurrencyRepository $currencyRepository,
        private PaymentService $paymentService
    ) {
    }

    public function createReservation(
        Merchant $merchant,
        Space $space,
        Periodicity $periodicity,
        int $duration
    ): SpaceReservation {
        // Validate space is available
        if ($space->getStatus() !== SpaceStatus::AVAILABLE) {
            throw new \RuntimeException('This space is not available for reservation.');
        }

        // Calculate price
        $price = $this->pricingService->calculatePrice($space, $periodicity, $duration);
        
        if (!$price) {
            throw new \RuntimeException('No pricing rule found for this space and periodicity.');
        }

        // Get default currency (CDF)
        $currency = $this->currencyRepository->findOneBy(['code' => 'CDF']);

        // Create reservation
        $reservation = new SpaceReservation();
        $reservation->setMerchant($merchant);
        $reservation->setSpace($space);
        $reservation->setPeriodicity($periodicity);
        $reservation->setDuration($duration);
        $reservation->setFirstPaymentAmount($price);
        $reservation->setCurrency($currency);
        $reservation->setStatus(ReservationStatus::PENDING_ADMIN);

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return $reservation;
    }

    public function approveReservation(SpaceReservation $reservation): void
    {
        if ($reservation->getStatus() !== ReservationStatus::PENDING_ADMIN) {
            throw new \RuntimeException('Only pending reservations can be approved.');
        }

        $reservation->setStatus(ReservationStatus::APPROVED);
        
        // Mark space as occupied
        $space = $reservation->getSpace();
        $space->setStatus(SpaceStatus::OCCUPIED);

        // Create Contract
        $contract = new \App\Entity\Contract();
        $contract->setMerchant($reservation->getMerchant());
        $contract->setSpace($space);
        $contract->setContractCode('CTR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));
        $contract->setStartDate(new \DateTime());
        
        // Calculate End Date based on duration and periodicity
        $endDate = new \DateTime();
        $duration = $reservation->getDuration();
        $periodicity = $reservation->getPeriodicity();
        
        $intervalSpec = match($periodicity) {
            Periodicity::DAY => 'P' . $duration . 'D',
            Periodicity::WEEK => 'P' . $duration . 'W',
            Periodicity::MONTH => 'P' . $duration . 'M',
            Periodicity::QUARTER => 'P' . ($duration * 3) . 'M',
            Periodicity::SEMESTER => 'P' . ($duration * 6) . 'M',
            Periodicity::YEAR => 'P' . $duration . 'Y',
        };
        
        $endDate->add(new \DateInterval($intervalSpec));
        $contract->setEndDate($endDate);

        // Logic for Rent/Guarantee (Simplified for now, assuming FirstPaymentAmount is the base)
        // Ideally we should recalculate based on business rules (e.g. 3 months guarantee)
        $contract->setRentAmount($reservation->getFirstPaymentAmount());
        $contract->setGuaranteeAmount($reservation->getFirstPaymentAmount()); 
        
        // Map Periodicity to BillingCycle
        $billingCycle = match($periodicity) {
            Periodicity::MONTH => \App\Enum\BillingCycle::MONTHLY,
            Periodicity::QUARTER => \App\Enum\BillingCycle::QUARTERLY,
            Periodicity::YEAR => \App\Enum\BillingCycle::YEARLY,
            default => \App\Enum\BillingCycle::MONTHLY, // Default fallback
        };
        $contract->setBillingCycle($billingCycle);
        
        $contract->setStatus(\App\Enum\ContractStatus::PENDING_SIGNATURE);

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        // Create Initial Invoice (Payment)
        // Amount = Guarantee + First Rent (or just Guarantee if Rent is paid later? Let's say both)
        $totalAmount = (float) $contract->getGuaranteeAmount() + (float) $contract->getRentAmount();
        
        $this->paymentService->createPayment(
            $reservation->getMerchant(),
            $totalAmount,
            'reservation',
            $contract,
            null, // Auto-generate reference
            new \DateTime('+7 days'), // Due in 7 days
            'CDF'
        );
    }

    public function rejectReservation(SpaceReservation $reservation, string $reason): void
    {
        if ($reservation->getStatus() !== ReservationStatus::PENDING_ADMIN) {
            throw new \RuntimeException('Only pending reservations can be rejected.');
        }

        $reservation->setStatus(ReservationStatus::REJECTED);
        $reservation->setRejectionReason($reason);

        $this->entityManager->flush();
    }

    public function cancelReservation(SpaceReservation $reservation): void
    {
        if ($reservation->getStatus() === ReservationStatus::APPROVED) {
            // If already approved, release the space
            $space = $reservation->getSpace();
            $space->setStatus(SpaceStatus::AVAILABLE);
        }

        $reservation->setStatus(ReservationStatus::CANCELLED);
        $this->entityManager->flush();
    }

    public function getPendingReservations(): array
    {
        return $this->reservationRepository->findPendingReservations();
    }

    public function getMerchantReservations(Merchant $merchant): array
    {
        return $this->reservationRepository->findByMerchant($merchant);
    }
}

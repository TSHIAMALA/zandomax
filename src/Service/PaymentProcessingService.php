<?php

namespace App\Service;

use App\Entity\Payment;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

class PaymentProcessingService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function processPayment(Payment $payment): bool
    {
        // Mock Logic: Simulate processing delay
        // sleep(1);

        // Mock Logic: 90% success rate
        $isSuccessful = rand(1, 100) <= 90;

        if ($isSuccessful) {
            $payment->setStatus(PaymentStatus::PAID);
            $payment->setPaymentDate(new \DateTime());
            $payment->setBankingTransactionId('TXN-' . strtoupper(uniqid()));
        } else {
            $payment->setStatus(PaymentStatus::FAILED);
        }

        $this->entityManager->flush();

        return $isSuccessful;
    }

    public function initiateRefund(Payment $payment): void
    {
        if ($payment->getStatus() !== PaymentStatus::PAID) {
            throw new \RuntimeException('Cannot refund a payment that is not paid.');
        }

        $payment->setStatus(PaymentStatus::REFUNDED);
        $this->entityManager->flush();
    }
}

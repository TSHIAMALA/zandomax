<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\Payment;
use App\Enum\TransactionType;
use App\Enum\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MobileMoneyService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Initier un paiement Airtel Money
     */
    public function initiateAirtelMoneyPayment(Payment $payment, string $phoneNumber): Transaction
    {
        $transaction = new Transaction();
        $transaction->setPayment($payment);
        $transaction->setMerchant($payment->getMerchant());
        $transaction->setType(TransactionType::PAYMENT);
        $transaction->setMethod(PaymentMethod::AIRTEL_MONEY);
        $transaction->setAmount($payment->getAmount());
        $transaction->setCurrency($payment->getCurrency());
        $transaction->setPhoneNumber($phoneNumber);
        $transaction->setDescription("Paiement Airtel Money - " . $payment->getType()->value);
        $transaction->setStatus('pending');

        // TODO: Intégration réelle avec l'API Airtel Money
        // Pour le moment, simulation
        $transaction->setMetadata([
            'provider' => 'airtel_money',
            'initiated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'simulation' => true
        ]);

        $this->em->persist($transaction);
        $this->em->flush();

        $this->logger->info('Airtel Money payment initiated', [
            'transaction_id' => $transaction->getId(),
            'amount' => $payment->getAmount(),
            'phone' => $phoneNumber
        ]);

        return $transaction;
    }

    /**
     * Initier un paiement M-Pesa
     */
    public function initiateMpesaPayment(Payment $payment, string $phoneNumber): Transaction
    {
        $transaction = new Transaction();
        $transaction->setPayment($payment);
        $transaction->setMerchant($payment->getMerchant());
        $transaction->setType(TransactionType::PAYMENT);
        $transaction->setMethod(PaymentMethod::MPESA);
        $transaction->setAmount($payment->getAmount());
        $transaction->setCurrency($payment->getCurrency());
        $transaction->setPhoneNumber($phoneNumber);
        $transaction->setDescription("Paiement M-Pesa - " . $payment->getType()->value);
        $transaction->setStatus('pending');

        // TODO: Intégration réelle avec l'API M-Pesa
        $transaction->setMetadata([
            'provider' => 'mpesa',
            'initiated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'simulation' => true
        ]);

        $this->em->persist($transaction);
        $this->em->flush();

        $this->logger->info('M-Pesa payment initiated', [
            'transaction_id' => $transaction->getId(),
            'amount' => $payment->getAmount(),
            'phone' => $phoneNumber
        ]);

        return $transaction;
    }

    /**
     * Initier un paiement Orange Money
     */
    public function initiateOrangeMoneyPayment(Payment $payment, string $phoneNumber): Transaction
    {
        $transaction = new Transaction();
        $transaction->setPayment($payment);
        $transaction->setMerchant($payment->getMerchant());
        $transaction->setType(TransactionType::PAYMENT);
        $transaction->setMethod(PaymentMethod::ORANGE_MONEY);
        $transaction->setAmount($payment->getAmount());
        $transaction->setCurrency($payment->getCurrency());
        $transaction->setPhoneNumber($phoneNumber);
        $transaction->setDescription("Paiement Orange Money - " . $payment->getType()->value);
        $transaction->setStatus('pending');

        // TODO: Intégration réelle avec l'API Orange Money
        $transaction->setMetadata([
            'provider' => 'orange_money',
            'initiated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'simulation' => true
        ]);

        $this->em->persist($transaction);
        $this->em->flush();

        $this->logger->info('Orange Money payment initiated', [
            'transaction_id' => $transaction->getId(),
            'amount' => $payment->getAmount(),
            'phone' => $phoneNumber
        ]);

        return $transaction;
    }

    /**
     * Vérifier le statut d'une transaction
     */
    public function checkTransactionStatus(Transaction $transaction): string
    {
        // TODO: Vérifier le statut réel auprès du provider
        // Pour le moment, simulation
        
        // Simuler une réussite après 30 secondes
        $createdAt = $transaction->getCreatedAt();
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $createdAt->getTimestamp();

        if ($diff > 30 && $transaction->getStatus() === 'pending') {
            // Simuler une réussite
            $this->completeTransaction($transaction);
            return 'completed';
        }

        return $transaction->getStatus();
    }

    /**
     * Compléter une transaction
     */
    public function completeTransaction(Transaction $transaction): void
    {
        $transaction->setStatus('completed');
        $transaction->setCompletedAt(new \DateTime());
        
        // Mettre à jour le paiement associé
        $payment = $transaction->getPayment();
        $payment->setStatus(\App\Enum\PaymentStatus::COMPLETED);
        
        $this->em->flush();

        $this->logger->info('Transaction completed', [
            'transaction_id' => $transaction->getId()
        ]);
    }

    /**
     * Marquer une transaction comme échouée
     */
    public function failTransaction(Transaction $transaction, string $errorMessage): void
    {
        $transaction->setStatus('failed');
        $transaction->setErrorMessage($errorMessage);
        
        $this->em->flush();

        $this->logger->error('Transaction failed', [
            'transaction_id' => $transaction->getId(),
            'error' => $errorMessage
        ]);
    }
}

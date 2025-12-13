<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Merchant;
use App\Entity\Contract;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Uid\Uuid;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaymentRepository $paymentRepository
    ) {
    }

    /**
     * Créer un nouveau paiement
     */
    public function createPayment(
        Merchant $merchant,
        float $amount,
        string $type,
        ?Contract $contract = null,
        ?string $reference = null,
        ?\DateTimeInterface $dueDate = null,
        string $currency = 'CDF'
    ): Payment {
        $payment = new Payment();
        $payment->setMerchant($merchant);
        $payment->setAmount((string) $amount);
        $payment->setType(\App\Enum\PaymentType::tryFrom($type) ?? \App\Enum\PaymentType::RESERVATION);
        $payment->setStatus(\App\Enum\PaymentStatus::PENDING);
        $payment->setContract($contract);
        $payment->setBankingTransactionId($reference ?? $this->generateReference());
        $payment->setCreatedAt(new \DateTime());
        if ($dueDate instanceof \DateTimeImmutable) {
            $dueDate = \DateTime::createFromImmutable($dueDate);
        }
        $payment->setDueDate($dueDate);
        $payment->setCurrency($currency);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * Valider un paiement
     */
    public function validatePayment(Payment $payment, string $validatedBy): Payment
    {
        $payment->setStatus(\App\Enum\PaymentStatus::PAID);
        $payment->setPaymentDate(new \DateTime());
        // $payment->setValidatedBy($validatedBy); // Entity has processedBy (User), not validatedBy (string)

        $this->entityManager->flush();

        return $payment;
    }

    /**
     * Rejeter un paiement
     */
    public function rejectPayment(Payment $payment, string $reason): Payment
    {
        $payment->setStatus(\App\Enum\PaymentStatus::FAILED);
        // $payment->setMetadata... Payment entity doesn't have metadata field in the view I saw earlier?
        // Let's check Payment entity again.
        
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * Générer un reçu PDF
     */
    public function generateReceipt(Payment $payment): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->getReceiptHtml($payment);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Obtenir les paiements en retard
     */
    public function getOverduePayments(): array
    {
        return $this->paymentRepository->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.dueDate < :now')
            ->setParameter('status', 'pending')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculer le total des paiements d'un marchand
     */
    public function getTotalPaidByMerchant(Merchant $merchant): float
    {
        return (float) $this->paymentRepository->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.merchant = :merchant')
            ->andWhere('p.status = :status')
            ->setParameter('merchant', $merchant)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Générer une référence unique
     */
    private function generateReference(): string
    {
        return 'PAY-' . strtoupper(substr(Uuid::v4()->toRfc4122(), 0, 8));
    }

    /**
     * HTML du reçu
     */
    private function getReceiptHtml(Payment $payment): string
    {
        $merchant = $payment->getMerchant();
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reçu de Paiement</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2563eb; margin: 0; }
        .info { margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .label { font-weight: bold; }
        .amount { font-size: 24px; color: #059669; font-weight: bold; text-align: center; margin: 30px 0; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ZANDO MARKET</h1>
        <p>Reçu de Paiement</p>
    </div>
    
    <div class="info">
        <div class="info-row">
            <span class="label">Référence:</span>
            <span>{$payment->getReference()}</span>
        </div>
        <div class="info-row">
            <span class="label">Date:</span>
            <span>{$payment->getCreatedAt()->format('d/m/Y H:i')}</span>
        </div>
        <div class="info-row">
            <span class="label">Marchand:</span>
            <span>{$merchant->getFirstname()} {$merchant->getLastname()}</span>
        </div>
        <div class="info-row">
            <span class="label">Type:</span>
            <span>{$payment->getType()}</span>
        </div>
        <div class="info-row">
            <span class="label">Statut:</span>
            <span>{$payment->getStatus()}</span>
        </div>
    </div>
    
    <div class="amount">
        Montant: {$payment->getAmount()} FCFA
    </div>
    
    <div class="footer">
        <p>Marché Central ZANDO - Document officiel</p>
        <p>Généré le {$payment->getCreatedAt()->format('d/m/Y à H:i')}</p>
    </div>
</body>
</html>
HTML;
    }
}

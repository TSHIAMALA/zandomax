<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\Contract;
use App\Enum\InvoiceStatus;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class InvoiceGenerationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Environment $twig
    ) {
    }

    public function generateInvoiceForPayment(Payment $payment): Invoice
    {
        $invoice = new Invoice();
        $invoice->setInvoiceNumber($this->generateInvoiceNumber());
        $invoice->setMerchant($payment->getMerchant());
        $invoice->setPayment($payment);
        $invoice->setContract($payment->getContract());
        $invoice->setAmount($payment->getAmount());
        $invoice->setCurrency($payment->getCurrency());
        
        // Calculer la taxe (exemple: 16% TVA)
        $taxRate = 0.16;
        $taxAmount = (float)$payment->getAmount() * $taxRate;
        $invoice->setTaxAmount(number_format($taxAmount, 2, '.', ''));
        
        $totalAmount = (float)$payment->getAmount() + $taxAmount;
        $invoice->setTotalAmount(number_format($totalAmount, 2, '.', ''));
        
        $invoice->setDescription("Paiement de " . $payment->getType()->value);
        $invoice->setStatus(InvoiceStatus::PENDING);

        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    public function generateInvoiceForContract(Contract $contract): Invoice
    {
        $invoice = new Invoice();
        $invoice->setInvoiceNumber($this->generateInvoiceNumber());
        $invoice->setMerchant($contract->getMerchant());
        $invoice->setContract($contract);
        $invoice->setAmount($contract->getRentAmount());
        $invoice->setCurrency('CDF');
        
        // Calculer la taxe
        $taxRate = 0.16;
        $taxAmount = (float)$contract->getRentAmount() * $taxRate;
        $invoice->setTaxAmount(number_format($taxAmount, 2, '.', ''));
        
        $totalAmount = (float)$contract->getRentAmount() + $taxAmount;
        $invoice->setTotalAmount(number_format($totalAmount, 2, '.', ''));
        
        $invoice->setDescription("Loyer mensuel - " . $contract->getContractCode());
        $invoice->setStatus(InvoiceStatus::PENDING);

        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    public function generateInvoicePdf(Invoice $invoice): string
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($pdfOptions);
        
        $html = $this->twig->render('pdf/invoice.html.twig', [
            'invoice' => $invoice
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
    }

    public function markInvoiceAsPaid(Invoice $invoice): void
    {
        $invoice->setStatus(InvoiceStatus::PAID);
        $invoice->setPaidAt(new \DateTime());
        $invoice->setUpdatedAt(new \DateTime());
        
        $this->em->flush();
    }

    public function markInvoiceAsOverdue(Invoice $invoice): void
    {
        if ($invoice->isOverdue() && $invoice->getStatus() === InvoiceStatus::PENDING) {
            $invoice->setStatus(InvoiceStatus::OVERDUE);
            $invoice->setUpdatedAt(new \DateTime());
            $this->em->flush();
        }
    }

    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Compter les factures du mois
        $count = $this->em->getRepository(Invoice::class)
            ->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('YEAR(i.createdAt) = :year')
            ->andWhere('MONTH(i.createdAt) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();
        
        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        return "INV-{$year}{$month}-{$sequence}";
    }
}

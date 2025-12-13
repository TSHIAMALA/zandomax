<?php

namespace App\Controller\MarketAdmin;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/invoices', name: 'market_admin_invoices_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private InvoiceGenerationService $invoiceService
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');
        
        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->leftJoin('i.merchant', 'm')
            ->addSelect('m')
            ->orderBy('i.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }

        $invoices = $qb->getQuery()->getResult();

        // Statistiques
        $totalAmount = $this->invoiceRepository->createQueryBuilder('i')
            ->select('SUM(i.totalAmount)')
            ->where('i.status = :paid')
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $pendingAmount = $this->invoiceRepository->createQueryBuilder('i')
            ->select('SUM(i.totalAmount)')
            ->where('i.status = :pending')
            ->setParameter('pending', 'pending')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $overdueCount = $this->invoiceRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.status = :overdue')
            ->setParameter('overdue', 'overdue')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('market_admin/invoices/index.html.twig', [
            'invoices' => $invoices,
            'currentStatus' => $status,
            'stats' => [
                'totalPaid' => $totalAmount,
                'pending' => $pendingAmount,
                'overdueCount' => $overdueCount,
            ],
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(string $id): Response
    {
        $invoice = $this->invoiceRepository->find(hex2bin($id));

        if (!$invoice) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        return $this->render('market_admin/invoices/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/pdf', name: 'pdf')]
    public function downloadPdf(string $id): Response
    {
        $invoice = $this->invoiceRepository->find(hex2bin($id));

        if (!$invoice) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        $pdfContent = $this->invoiceService->generateInvoicePdf($invoice);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facture-' . $invoice->getInvoiceNumber() . '.pdf"'
        ]);
    }

    #[Route('/{id}/mark-paid', name: 'mark_paid', methods: ['POST'])]
    public function markAsPaid(string $id): Response
    {
        $invoice = $this->invoiceRepository->find(hex2bin($id));

        if (!$invoice) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        $this->invoiceService->markInvoiceAsPaid($invoice);
        $this->addFlash('success', 'Facture marquée comme payée');

        return $this->redirectToRoute('market_admin_invoices_show', ['id' => $id]);
    }
}

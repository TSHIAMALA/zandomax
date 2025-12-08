<?php

namespace App\Controller;

use App\Repository\MerchantRepository;
use App\Repository\SpaceRepository;
use App\Repository\ContractRepository;
use App\Repository\PaymentRepository;
use App\Repository\SpaceReservationRepository;
use App\Service\SpaceReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'market_admin_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class MarketAdminController extends AbstractController
{
    public function __construct(
        private MerchantRepository $merchantRepository,
        private SpaceRepository $spaceRepository,
        private ContractRepository $contractRepository,
        private PaymentRepository $paymentRepository,
        private SpaceReservationRepository $reservationRepository,
        private SpaceReservationService $reservationService
    ) {
    }

    #[Route('', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Get statistics
        $totalMerchants = $this->merchantRepository->count([]);
        $activeMerchants = $this->merchantRepository->count(['status' => 'active']);
        $pendingMerchants = $this->merchantRepository->count(['status' => 'pending_validation']);
        
        $totalSpaces = $this->spaceRepository->count([]);
        $availableSpaces = $this->spaceRepository->count(['status' => 'available']);
        $occupiedSpaces = $this->spaceRepository->count(['status' => 'occupied']);
        
        $totalContracts = $this->contractRepository->count([]);
        $activeContracts = $this->contractRepository->count(['status' => 'active']);
        
        $totalPayments = $this->paymentRepository->count([]);
        $pendingPayments = $this->paymentRepository->count(['status' => 'pending']);
        $completedPayments = $this->paymentRepository->count(['status' => 'completed']);

        return $this->render('market_admin/dashboard.html.twig', [
            'stats' => [
                'merchants' => [
                    'total' => $totalMerchants,
                    'active' => $activeMerchants,
                    'pending' => $pendingMerchants,
                ],
                'spaces' => [
                    'total' => $totalSpaces,
                    'available' => $availableSpaces,
                    'occupied' => $occupiedSpaces,
                ],
                'contracts' => [
                    'total' => $totalContracts,
                    'active' => $activeContracts,
                ],
                'payments' => [
                    'total' => $totalPayments,
                    'pending' => $pendingPayments,
                    'completed' => $completedPayments,
                ],
            ],
        ]);
    }



    #[Route('/merchants/{id}', name: 'merchants_show')]
    public function merchantsShow(string $id): Response
    {
        $merchant = $this->merchantRepository->find(hex2bin($id));

        if (!$merchant) {
            throw $this->createNotFoundException('Merchant not found');
        }

        // Get merchant's contracts
        $contracts = $this->contractRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC']
        );

        // Get merchant's payments
        $payments = $this->paymentRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC'],
            10
        );

        return $this->render('market_admin/merchants/show.html.twig', [
            'merchant' => $merchant,
            'contracts' => $contracts,
            'payments' => $payments,
        ]);
    }



    #[Route('/contracts', name: 'contracts_index')]
    public function contractsIndex(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $status = $request->query->get('status');

        $qb = $this->contractRepository->createQueryBuilder('c')
            ->leftJoin('c.merchant', 'm')
            ->leftJoin('c.space', 's')
            ->addSelect('m', 's');

        if ($status) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        $totalCount = (clone $qb)->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        $contracts = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('market_admin/contracts/index.html.twig', [
            'contracts' => $contracts,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    #[Route('/contracts/{id}', name: 'contracts_show')]
    public function contractsShow(string $id): Response
    {
        $contract = $this->contractRepository->find(hex2bin($id));

        if (!$contract) {
            throw $this->createNotFoundException('Contract not found');
        }

        // Get contract payments
        $payments = $this->paymentRepository->findBy(
            ['contract' => $contract],
            ['createdAt' => 'DESC']
        );

        return $this->render('market_admin/contracts/show.html.twig', [
            'contract' => $contract,
            'payments' => $payments,
        ]);
    }

    #[Route('/payments', name: 'payments_index')]
    public function paymentsIndex(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $status = $request->query->get('status');
        $type = $request->query->get('type');

        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->leftJoin('p.merchant', 'm')
            ->leftJoin('p.contract', 'c')
            ->addSelect('m', 'c');

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        $totalCount = (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $payments = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculate summary statistics
        $totalAmount = $this->paymentRepository->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.status = :completed')
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $pendingAmount = $this->paymentRepository->createQueryBuilder('p')
            ->select('SUM(p.amount)')
            ->where('p.status = :pending')
            ->setParameter('pending', 'pending')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('market_admin/payments/index.html.twig', [
            'payments' => $payments,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
            'summary' => [
                'totalCollected' => $totalAmount,
                'pending' => $pendingAmount,
            ],
        ]);
    }

    #[Route('/reservations', name: 'reservations_index')]
    public function reservationsIndex(Request $request): Response
    {
        $status = $request->query->get('status', 'pending_admin');
        
        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.merchant', 'm')
            ->leftJoin('r.space', 's')
            ->leftJoin('s.spaceCategory', 'sc')
            ->addSelect('m', 's', 'sc')
            ->where('r.isDeleted = :deleted')
            ->setParameter('deleted', false);

        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        $reservations = $qb
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('market_admin/reservations/index.html.twig', [
            'reservations' => $reservations,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/reservations/{id}/approve', name: 'reservations_approve', methods: ['POST'])]
    public function approveReservation(string $id): Response
    {
        $reservation = $this->reservationRepository->find(hex2bin($id));

        if (!$reservation) {
            $this->addFlash('error', 'Réservation non trouvée');
            return $this->redirectToRoute('market_admin_reservations_index');
        }

        try {
            $this->reservationService->approveReservation($reservation);
            $this->addFlash('success', 'Réservation approuvée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('market_admin_reservations_index');
    }

    #[Route('/reservations/{id}/reject', name: 'reservations_reject', methods: ['POST'])]
    public function rejectReservation(string $id, Request $request): Response
    {
        $reservation = $this->reservationRepository->find(hex2bin($id));

        if (!$reservation) {
            $this->addFlash('error', 'Réservation non trouvée');
            return $this->redirectToRoute('market_admin_reservations_index');
        }

        $reason = $request->request->get('reason', 'Aucun motif fourni');

        try {
            $this->reservationService->rejectReservation($reservation, $reason);
            $this->addFlash('success', 'Réservation rejetée');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('market_admin_reservations_index');
    }
    
    #[Route('/contracts/{id}/pdf', name: 'contracts_pdf')]
    public function contractPdf(string $id, \App\Service\ContractGenerationService $pdfService): Response
    {
        $contract = $this->contractRepository->find(hex2bin($id));

        if (!$contract) {
            throw $this->createNotFoundException('Contract not found');
        }

        $pdfContent = $pdfService->generateContractPdf($contract);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="contract-' . $contract->getContractCode() . '.pdf"'
        ]);
    }
    
    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        return $this->render('market_admin/settings.html.twig');
    }
}

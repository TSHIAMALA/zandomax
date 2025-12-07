<?php

namespace App\Controller;

use App\Repository\ContractRepository;
use App\Repository\PaymentRepository;
use App\Repository\SpaceRepository;
use App\Repository\SpaceReservationRepository;
use App\Repository\PricingRuleRepository;
use App\Service\PaymentService;
use App\Service\SpaceReservationService;
use App\Service\DocumentService;
use App\Service\PricingService;
use App\Enum\Periodicity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/merchant', name: 'merchant_')]
#[IsGranted('ROLE_MERCHANT')]
class MerchantPortalController extends AbstractController
{
    public function __construct(
        private ContractRepository $contractRepository,
        private PaymentRepository $paymentRepository,
        private PaymentService $paymentService,
        private SpaceRepository $spaceRepository,
        private SpaceReservationRepository $reservationRepository,
        private SpaceReservationService $reservationService,
        private DocumentService $documentService,
        private PricingService $pricingService
    ) {
    }

    #[Route('', name: 'dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        if (!$merchant) {
            throw $this->createNotFoundException('Aucun marchand associé');
        }

        $activeContract = $this->contractRepository->findOneBy([
            'merchant' => $merchant,
            'status' => 'active'
        ]);

        $recentPayments = $this->paymentRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC'],
            5
        );

        $totalPaid = $this->paymentService->getTotalPaidByMerchant($merchant);

        return $this->render('merchant/dashboard.html.twig', [
            'merchant' => $merchant,
            'contract' => $activeContract,
            'recentPayments' => $recentPayments,
            'totalPaid' => $totalPaid,
        ]);
    }

    #[Route('/payments', name: 'payments')]
    public function payments(): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $payments = $this->paymentRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC']
        );

        return $this->render('merchant/payments.html.twig', [
            'payments' => $payments,
            'merchant' => $merchant,
        ]);
    }

    #[Route('/contract', name: 'contract')]
    public function contract(): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $contract = $this->contractRepository->findOneBy([
            'merchant' => $merchant,
            'status' => 'active'
        ]);

        return $this->render('merchant/contract.html.twig', [
            'contract' => $contract,
            'merchant' => $merchant,
        ]);
    }

    #[Route('/spaces', name: 'spaces')]
    public function spaces(Request $request): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $zone = $request->query->get('zone');
        $spaceTypeId = $request->query->get('space_type_id');

        $qb = $this->spaceRepository->createQueryBuilder('s')
            ->leftJoin('s.spaceCategory', 'sc')
            ->leftJoin('s.spaceType', 'st')
            ->addSelect('sc', 'st')
            ->where('s.status = :status')
            ->andWhere('s.isDeleted = :deleted')
            ->setParameter('status', 'available')
            ->setParameter('deleted', false);

        if ($zone) {
            $qb->andWhere('s.zone = :zone')
               ->setParameter('zone', $zone);
        }

        if ($spaceTypeId) {
            $qb->andWhere('s.spaceType = :spaceType')
               ->setParameter('spaceType', hex2bin($spaceTypeId));
        }

        $spaces = $qb->orderBy('s.code', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('merchant/spaces.html.twig', [
            'spaces' => $spaces,
            'merchant' => $merchant,
            'filters' => [
                'zone' => $zone,
                'space_type_id' => $spaceTypeId,
            ],
        ]);
    }

    #[Route('/reserve/{id}', name: 'reserve')]
    public function reserve(string $id, Request $request): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();
        $space = $this->spaceRepository->find(hex2bin($id));

        if (!$space) {
            throw $this->createNotFoundException('Espace non trouvé');
        }

        if ($request->isMethod('POST')) {
            try {
                $periodicity = Periodicity::from($request->request->get('periodicity'));
                $duration = (int) $request->request->get('duration');

                $reservation = $this->reservationService->createReservation(
                    $merchant,
                    $space,
                    $periodicity,
                    $duration
                );

                $this->addFlash('success', 'Réservation créée avec succès ! En attente de validation.');
                return $this->redirectToRoute('merchant_reservations');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }
        }

        $pricingOptions = $this->pricingService->getAvailablePeriodicitiesForSpace($space);

        return $this->render('merchant/reserve.html.twig', [
            'space' => $space,
            'merchant' => $merchant,
            'pricingOptions' => $pricingOptions,
        ]);
    }

    #[Route('/reservations', name: 'reservations')]
    public function reservations(): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $reservations = $this->reservationRepository->findBy(
            ['merchant' => $merchant, 'isDeleted' => false],
            ['createdAt' => 'DESC']
        );

        return $this->render('merchant/reservations.html.twig', [
            'reservations' => $reservations,
            'merchant' => $merchant,
        ]);
    }

    #[Route('/documents', name: 'documents')]
    public function documents(Request $request): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        if ($request->isMethod('POST') && $request->files->get('file')) {
            try {
                $file = $request->files->get('file');
                $documentType = $request->request->get('type', 'other');

                $this->documentService->uploadDocument($merchant, $file, $documentType);
                $this->addFlash('success', 'Document uploadé avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
            }

            return $this->redirectToRoute('merchant_documents');
        }

        $documents = $merchant->getDocuments() ?? [];

        return $this->render('merchant/documents.html.twig', [
            'documents' => $documents,
            'merchant' => $merchant,
        ]);
    }

    #[Route('/documents/{filename}/delete', name: 'documents_delete', methods: ['POST'])]
    public function deleteDocument(string $filename): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        try {
            $this->documentService->deleteDocument($merchant, $filename);
            $this->addFlash('success', 'Document supprimé');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('merchant_documents');
    }

    #[Route('/contract/{id}/download', name: 'contract_download')]
    public function downloadContract(string $id, \App\Service\ContractGenerationService $contractGenerationService): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();
        $contract = $this->contractRepository->find(hex2bin($id));

        if (!$contract || $contract->getMerchant() !== $merchant) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $pdfContent = $contractGenerationService->generateContractPdf($contract);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="contrat_' . $contract->getContractCode() . '.pdf"',
        ]);
    }

    #[Route('/payments/{id}/pay', name: 'payment_pay')]
    public function pay(string $id, Request $request): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();
        $payment = $this->paymentRepository->find(hex2bin($id));

        if (!$payment || $payment->getMerchant() !== $merchant) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        if ($payment->getStatus() === \App\Enum\PaymentStatus::PAID) {
            $this->addFlash('warning', 'Ce paiement a déjà été effectué.');
            return $this->redirectToRoute('merchant_payments');
        }

        if ($request->isMethod('POST')) {
            // Simulate payment processing
            $this->paymentService->validatePayment($payment, 'SYSTEM');
            
            $this->addFlash('success', 'Paiement effectué avec succès !');
            return $this->redirectToRoute('merchant_payments');
        }

        return $this->render('merchant/payment_gateway.html.twig', [
            'payment' => $payment,
            'merchant' => $merchant
        ]);
    }
}

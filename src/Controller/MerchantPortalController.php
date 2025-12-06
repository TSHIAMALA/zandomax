<?php

namespace App\Controller;

use App\Repository\ContractRepository;
use App\Repository\PaymentRepository;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        private PaymentService $paymentService
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
}

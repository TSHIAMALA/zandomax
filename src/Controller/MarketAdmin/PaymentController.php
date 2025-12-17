<?php

namespace App\Controller\MarketAdmin;

use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/payments', name: 'market_admin_payments_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class PaymentController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(PaymentRepository $paymentRepository): Response
    {
        $payments = $paymentRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('market_admin/payments/index.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(string $id, PaymentRepository $paymentRepository): Response
    {
        $payment = $paymentRepository->find(hex2bin($id));
        
        if (!$payment) {
            throw $this->createNotFoundException('Paiement non trouvé');
        }

        return $this->render('market_admin/payments/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validate(
        string $id, 
        PaymentRepository $paymentRepository,
        EntityManagerInterface $em
    ): Response {
        $payment = $paymentRepository->find(hex2bin($id));
        
        if (!$payment) {
            throw $this->createNotFoundException('Paiement non trouvé');
        }

        if ($payment->getStatus()->value !== 'pending') {
            $this->addFlash('error', 'Ce paiement ne peut pas être validé.');
            return $this->redirectToRoute('market_admin_payments_show', ['id' => $id]);
        }

        $payment->setStatus(\App\Enum\PaymentStatus::PAID);
        $payment->setPaymentDate(new \DateTime());
        $payment->setProcessedBy($this->getUser());
        $em->flush();

        $this->addFlash('success', 'Paiement validé avec succès. Le marchand peut maintenant être activé.');

        return $this->redirectToRoute('market_admin_payments_show', ['id' => $id]);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(
        string $id,
        Request $request,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $em
    ): Response {
        $payment = $paymentRepository->find(hex2bin($id));
        
        if (!$payment) {
            throw $this->createNotFoundException('Paiement non trouvé');
        }

        $payment->setStatus(\App\Enum\PaymentStatus::FAILED);
        $payment->setProcessedBy($this->getUser());
        $em->flush();

        $this->addFlash('warning', 'Paiement rejeté.');

        return $this->redirectToRoute('market_admin_payments_show', ['id' => $id]);
    }
}

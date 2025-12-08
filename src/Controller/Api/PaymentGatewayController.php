<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Service\MobileMoneyService;
use App\Service\InvoiceGenerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/payment-gateway', name: 'api_payment_gateway_')]
class PaymentGatewayController extends AbstractController
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private MobileMoneyService $mobileMoneyService,
        private InvoiceGenerationService $invoiceService,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/initiate', name: 'initiate', methods: ['POST'])]
    public function initiatePayment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $paymentId = $data['payment_id'] ?? null;
        $method = $data['method'] ?? null;
        $phoneNumber = $data['phone_number'] ?? null;

        if (!$paymentId || !$method || !$phoneNumber) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $payment = $this->paymentRepository->find(hex2bin($paymentId));
        if (!$payment) {
            return $this->json(['error' => 'Paiement non trouvé'], 404);
        }

        try {
            $transaction = match($method) {
                'airtel_money' => $this->mobileMoneyService->initiateAirtelMoneyPayment($payment, $phoneNumber),
                'mpesa' => $this->mobileMoneyService->initiateMpesaPayment($payment, $phoneNumber),
                'orange_money' => $this->mobileMoneyService->initiateOrangeMoneyPayment($payment, $phoneNumber),
                default => throw new \Exception('Méthode de paiement non supportée')
            };

            // Générer une facture
            $invoice = $this->invoiceService->generateInvoiceForPayment($payment);

            return $this->json([
                'success' => true,
                'transaction_id' => $transaction->getId(),
                'transaction_number' => $transaction->getTransactionNumber(),
                'invoice_id' => $invoice->getId(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'status' => $transaction->getStatus(),
                'message' => 'Paiement initié. Veuillez confirmer sur votre téléphone.'
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/check-status/{transactionId}', name: 'check_status', methods: ['GET'])]
    public function checkStatus(string $transactionId): JsonResponse
    {
        $transaction = $this->em->getRepository(\App\Entity\Transaction::class)
            ->find(hex2bin($transactionId));

        if (!$transaction) {
            return $this->json(['error' => 'Transaction non trouvée'], 404);
        }

        $status = $this->mobileMoneyService->checkTransactionStatus($transaction);

        return $this->json([
            'transaction_id' => $transaction->getId(),
            'status' => $status,
            'amount' => $transaction->getAmount(),
            'method' => $transaction->getMethod()->value,
        ]);
    }

    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        // TODO: Gérer les callbacks des providers Mobile Money
        $data = json_decode($request->getContent(), true);
        
        // Log pour debug
        file_put_contents('/tmp/payment_webhook.log', json_encode($data) . "\n", FILE_APPEND);

        return $this->json(['success' => true]);
    }
}

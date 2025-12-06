<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Repository\PaymentRepository;
use App\Repository\MerchantRepository;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/payments', name: 'api_payments_')]
class PaymentController extends AbstractController
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private MerchantRepository $merchantRepository,
        private ContractRepository $contractRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        // Get filters
        $status = $request->query->get('status');
        $type = $request->query->get('type');
        $merchantId = $request->query->get('merchant_id');
        $contractId = $request->query->get('contract_id');

        // Build query
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->leftJoin('p.merchant', 'm')
            ->leftJoin('p.contract', 'c')
            ->addSelect('m', 'c');

        // Apply filters
        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($merchantId) {
            $qb->andWhere('m.id = :merchantId')
               ->setParameter('merchantId', hex2bin($merchantId));
        }

        if ($contractId) {
            $qb->andWhere('c.id = :contractId')
               ->setParameter('contractId', hex2bin($contractId));
        }

        // Get total count
        $totalCount = (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        // Get paginated results
        $payments = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Serialize payments
        $data = array_map(function (Payment $payment) {
            return [
                'id' => bin2hex($payment->getId()),
                'type' => $payment->getType()->value,
                'amount' => $payment->getAmount(),
                'currency' => $payment->getCurrency(),
                'status' => $payment->getStatus()->value,
                'dueDate' => $payment->getDueDate()?->format('Y-m-d'),
                'paymentDate' => $payment->getPaymentDate()?->format('Y-m-d H:i:s'),
                'bankingTransactionId' => $payment->getBankingTransactionId(),
                'merchant' => [
                    'id' => bin2hex($payment->getMerchant()->getId()),
                    'name' => $payment->getMerchant()->getFirstname() . ' ' . $payment->getMerchant()->getLastname(),
                ],
                'contract' => $payment->getContract() ? [
                    'id' => bin2hex($payment->getContract()->getId()),
                    'contractCode' => $payment->getContract()->getContractCode(),
                ] : null,
                'createdAt' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $payments);

        return $this->json([
            'data' => $data,
            'meta' => [
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($totalCount / $limit),
            ],
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['merchant_id', 'type', 'amount'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->json([
                    'error' => "Missing required field: $field"
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $merchant = $this->merchantRepository->find(hex2bin($data['merchant_id']));
        if (!$merchant) {
            return $this->json(['error' => 'Invalid merchant'], Response::HTTP_BAD_REQUEST);
        }

        $payment = new Payment();
        $payment->setMerchant($merchant);
        $payment->setAmount($data['amount']);
        $payment->setCurrency($data['currency'] ?? 'CDF');

        // Set payment type
        try {
            $payment->setType(PaymentType::from($data['type']));
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid payment type',
                'validTypes' => array_map(fn($case) => $case->value, PaymentType::cases()),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Optional contract
        if (isset($data['contract_id'])) {
            $contract = $this->contractRepository->find(hex2bin($data['contract_id']));
            if ($contract) {
                $payment->setContract($contract);
            }
        }

        // Optional dates
        if (isset($data['due_date'])) {
            $payment->setDueDate(new \DateTime($data['due_date']));
        }
        if (isset($data['payment_date'])) {
            $payment->setPaymentDate(new \DateTime($data['payment_date']));
        }

        // Optional transaction ID
        if (isset($data['banking_transaction_id'])) {
            $payment->setBankingTransactionId($data['banking_transaction_id']);
        }

        // Set initiated by current user
        $payment->setInitiatedBy($this->getUser());

        // Validate
        $errors = $this->validator->validate($payment);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Payment created successfully',
            'id' => bin2hex($payment->getId()),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $payment = $this->paymentRepository->find(hex2bin($id));

        if (!$payment) {
            return $this->json(['error' => 'Payment not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => bin2hex($payment->getId()),
            'type' => $payment->getType()->value,
            'amount' => $payment->getAmount(),
            'currency' => $payment->getCurrency(),
            'status' => $payment->getStatus()->value,
            'dueDate' => $payment->getDueDate()?->format('Y-m-d'),
            'paymentDate' => $payment->getPaymentDate()?->format('Y-m-d H:i:s'),
            'bankingTransactionId' => $payment->getBankingTransactionId(),
            'merchant' => [
                'id' => bin2hex($payment->getMerchant()->getId()),
                'firstname' => $payment->getMerchant()->getFirstname(),
                'lastname' => $payment->getMerchant()->getLastname(),
                'phone' => $payment->getMerchant()->getPhone(),
            ],
            'contract' => $payment->getContract() ? [
                'id' => bin2hex($payment->getContract()->getId()),
                'contractCode' => $payment->getContract()->getContractCode(),
            ] : null,
            'createdAt' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        return $this->json($data);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PUT'])]
    public function updateStatus(string $id, Request $request): JsonResponse
    {
        $payment = $this->paymentRepository->find(hex2bin($id));

        if (!$payment) {
            return $this->json(['error' => 'Payment not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus) {
            return $this->json(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $statusEnum = PaymentStatus::from($newStatus);
            $payment->setStatus($statusEnum);

            // If payment is confirmed, set payment date
            if ($statusEnum === PaymentStatus::COMPLETED && !$payment->getPaymentDate()) {
                $payment->setPaymentDate(new \DateTime());
            }

            // Set processed by current user
            $payment->setProcessedBy($this->getUser());

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Payment status updated successfully',
                'id' => bin2hex($payment->getId()),
                'status' => $payment->getStatus()->value,
            ]);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid status value',
                'validStatuses' => array_map(fn($case) => $case->value, PaymentStatus::cases()),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/merchant/{merchantId}', name: 'merchant_history', methods: ['GET'])]
    public function merchantHistory(string $merchantId, Request $request): JsonResponse
    {
        $merchant = $this->merchantRepository->find(hex2bin($merchantId));

        if (!$merchant) {
            return $this->json(['error' => 'Merchant not found'], Response::HTTP_NOT_FOUND);
        }

        $payments = $this->paymentRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function (Payment $payment) {
            return [
                'id' => bin2hex($payment->getId()),
                'type' => $payment->getType()->value,
                'amount' => $payment->getAmount(),
                'currency' => $payment->getCurrency(),
                'status' => $payment->getStatus()->value,
                'dueDate' => $payment->getDueDate()?->format('Y-m-d'),
                'paymentDate' => $payment->getPaymentDate()?->format('Y-m-d H:i:s'),
                'createdAt' => $payment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $payments);

        return $this->json(['data' => $data]);
    }
}

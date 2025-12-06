<?php

namespace App\Controller\Api;

use App\Entity\Contract;
use App\Enum\ContractStatus;
use App\Enum\BillingCycle;
use App\Repository\ContractRepository;
use App\Repository\MerchantRepository;
use App\Repository\SpaceRepository;
use App\Service\ContractGenerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/contracts', name: 'api_contracts_')]
class ContractController extends AbstractController
{
    public function __construct(
        private ContractRepository $contractRepository,
        private MerchantRepository $merchantRepository,
        private SpaceRepository $spaceRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private ContractGenerationService $contractService
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
        $merchantId = $request->query->get('merchant_id');
        $spaceId = $request->query->get('space_id');
        $search = $request->query->get('search');

        // Build query
        $qb = $this->contractRepository->createQueryBuilder('c')
            ->leftJoin('c.merchant', 'm')
            ->leftJoin('c.space', 's')
            ->addSelect('m', 's');

        // Apply filters
        if ($status) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        if ($merchantId) {
            $qb->andWhere('m.id = :merchantId')
               ->setParameter('merchantId', hex2bin($merchantId));
        }

        if ($spaceId) {
            $qb->andWhere('s.id = :spaceId')
               ->setParameter('spaceId', hex2bin($spaceId));
        }

        if ($search) {
            $qb->andWhere('c.contractCode LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Get total count
        $totalCount = (clone $qb)->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        // Get paginated results
        $contracts = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Serialize contracts
        $data = array_map(function (Contract $contract) {
            return [
                'id' => bin2hex($contract->getId()),
                'contractCode' => $contract->getContractCode(),
                'startDate' => $contract->getStartDate()->format('Y-m-d'),
                'endDate' => $contract->getEndDate()->format('Y-m-d'),
                'rentAmount' => $contract->getRentAmount(),
                'guaranteeAmount' => $contract->getGuaranteeAmount(),
                'billingCycle' => $contract->getBillingCycle()->value,
                'status' => $contract->getStatus()->value,
                'merchant' => [
                    'id' => bin2hex($contract->getMerchant()->getId()),
                    'name' => $contract->getMerchant()->getFirstname() . ' ' . $contract->getMerchant()->getLastname(),
                ],
                'space' => [
                    'id' => bin2hex($contract->getSpace()->getId()),
                    'code' => $contract->getSpace()->getCode(),
                ],
                'createdAt' => $contract->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $contracts);

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

        $requiredFields = ['merchant_id', 'space_id', 'start_date', 'rent_amount', 'guarantee_amount'];
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

        $space = $this->spaceRepository->find(hex2bin($data['space_id']));
        if (!$space) {
            return $this->json(['error' => 'Invalid space'], Response::HTTP_BAD_REQUEST);
        }

        $contract = new Contract();
        $contract->setMerchant($merchant);
        $contract->setSpace($space);
        $contract->setContractCode('CNT-' . strtoupper(uniqid()));
        $contract->setStartDate(new \DateTime($data['start_date']));
        
        // Calculate end date based on billing cycle (default 1 year)
        $endDate = (new \DateTime($data['start_date']))->modify('+1 year');
        if (isset($data['end_date'])) {
            $endDate = new \DateTime($data['end_date']);
        }
        $contract->setEndDate($endDate);
        
        $contract->setRentAmount($data['rent_amount']);
        $contract->setGuaranteeAmount($data['guarantee_amount']);

        if (isset($data['billing_cycle'])) {
            try {
                $contract->setBillingCycle(BillingCycle::from($data['billing_cycle']));
            } catch (\ValueError $e) {
                return $this->json([
                    'error' => 'Invalid billing cycle',
                    'validCycles' => array_map(fn($case) => $case->value, BillingCycle::cases()),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate
        $errors = $this->validator->validate($contract);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Contract created successfully',
            'id' => bin2hex($contract->getId()),
            'contractCode' => $contract->getContractCode(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $contract = $this->contractRepository->find(hex2bin($id));

        if (!$contract) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => bin2hex($contract->getId()),
            'contractCode' => $contract->getContractCode(),
            'startDate' => $contract->getStartDate()->format('Y-m-d'),
            'endDate' => $contract->getEndDate()->format('Y-m-d'),
            'rentAmount' => $contract->getRentAmount(),
            'guaranteeAmount' => $contract->getGuaranteeAmount(),
            'billingCycle' => $contract->getBillingCycle()->value,
            'status' => $contract->getStatus()->value,
            'merchant' => [
                'id' => bin2hex($contract->getMerchant()->getId()),
                'firstname' => $contract->getMerchant()->getFirstname(),
                'lastname' => $contract->getMerchant()->getLastname(),
                'phone' => $contract->getMerchant()->getPhone(),
                'email' => $contract->getMerchant()->getEmail(),
            ],
            'space' => [
                'id' => bin2hex($contract->getSpace()->getId()),
                'code' => $contract->getSpace()->getCode(),
                'zone' => $contract->getSpace()->getZone(),
            ],
            'createdAt' => $contract->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        return $this->json($data);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $contract = $this->contractRepository->find(hex2bin($id));

        if (!$contract) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Update allowed fields
        if (isset($data['start_date'])) {
            $contract->setStartDate(new \DateTime($data['start_date']));
        }
        if (isset($data['end_date'])) {
            $contract->setEndDate(new \DateTime($data['end_date']));
        }
        if (isset($data['rent_amount'])) {
            $contract->setRentAmount($data['rent_amount']);
        }
        if (isset($data['guarantee_amount'])) {
            $contract->setGuaranteeAmount($data['guarantee_amount']);
        }
        if (isset($data['billing_cycle'])) {
            try {
                $contract->setBillingCycle(BillingCycle::from($data['billing_cycle']));
            } catch (\ValueError $e) {
                return $this->json([
                    'error' => 'Invalid billing cycle',
                    'validCycles' => array_map(fn($case) => $case->value, BillingCycle::cases()),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate
        $errors = $this->validator->validate($contract);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Contract updated successfully',
            'id' => bin2hex($contract->getId()),
        ]);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PUT'])]
    public function updateStatus(string $id, Request $request): JsonResponse
    {
        $contract = $this->contractRepository->find(hex2bin($id));

        if (!$contract) {
            return $this->json(['error' => 'Contract not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus) {
            return $this->json(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $statusEnum = ContractStatus::from($newStatus);
            $contract->setStatus($statusEnum);

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Contract status updated successfully',
                'id' => bin2hex($contract->getId()),
                'status' => $contract->getStatus()->value,
            ]);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid status value',
                'validStatuses' => array_map(fn($case) => $case->value, ContractStatus::cases()),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}

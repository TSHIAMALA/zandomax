<?php

namespace App\Controller\Api;

use App\Entity\Merchant;
use App\Enum\MerchantStatus;
use App\Repository\MerchantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/merchants', name: 'api_merchants_')]
class MerchantController extends AbstractController
{
    public function __construct(
        private MerchantRepository $merchantRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
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
        $search = $request->query->get('search');
        $categoryId = $request->query->get('category_id');

        // Build query
        $qb = $this->merchantRepository->createQueryBuilder('m')
            ->leftJoin('m.merchantCategory', 'mc')
            ->addSelect('mc');

        // Apply filters
        if ($status) {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $status);
        }

        if ($categoryId) {
            $qb->andWhere('mc.id = :categoryId')
               ->setParameter('categoryId', hex2bin($categoryId));
        }

        if ($search) {
            $qb->andWhere('m.firstname LIKE :search OR m.lastname LIKE :search OR m.phone LIKE :search OR m.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Get total count
        $totalCount = (clone $qb)->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        // Get paginated results
        $merchants = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Serialize merchants
        $data = array_map(function (Merchant $merchant) {
            return [
                'id' => bin2hex($merchant->getId()),
                'firstname' => $merchant->getFirstname(),
                'lastname' => $merchant->getLastname(),
                'phone' => $merchant->getPhone(),
                'email' => $merchant->getEmail(),
                'status' => $merchant->getStatus()->value,
                'kycLevel' => $merchant->getKycLevel()->value,
                'category' => [
                    'id' => bin2hex($merchant->getMerchantCategory()->getId()),
                    'name' => $merchant->getMerchantCategory()->getName(),
                ],
                'createdAt' => $merchant->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $merchants);

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

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $merchant = $this->merchantRepository->find(hex2bin($id));

        if (!$merchant) {
            return $this->json(['error' => 'Merchant not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => bin2hex($merchant->getId()),
            'firstname' => $merchant->getFirstname(),
            'lastname' => $merchant->getLastname(),
            'phone' => $merchant->getPhone(),
            'email' => $merchant->getEmail(),
            'accountNumber' => $merchant->getAccountNumber(),
            'status' => $merchant->getStatus()->value,
            'kycLevel' => $merchant->getKycLevel()->value,
            'biometricHash' => $merchant->getBiometricHash(),
            'category' => [
                'id' => bin2hex($merchant->getMerchantCategory()->getId()),
                'name' => $merchant->getMerchantCategory()->getName(),
            ],
            'createdAt' => $merchant->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $merchant->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];

        return $this->json($data);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $merchant = $this->merchantRepository->find(hex2bin($id));

        if (!$merchant) {
            return $this->json(['error' => 'Merchant not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Update allowed fields
        if (isset($data['firstname'])) {
            $merchant->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $merchant->setLastname($data['lastname']);
        }
        if (isset($data['phone'])) {
            $merchant->setPhone($data['phone']);
        }
        if (isset($data['email'])) {
            $merchant->setEmail($data['email']);
        }
        if (isset($data['accountNumber'])) {
            $merchant->setAccountNumber($data['accountNumber']);
        }

        $merchant->setUpdatedAt(new \DateTime());

        // Validate
        $errors = $this->validator->validate($merchant);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Merchant updated successfully',
            'id' => bin2hex($merchant->getId()),
        ]);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PUT'])]
    public function updateStatus(string $id, Request $request): JsonResponse
    {
        $merchant = $this->merchantRepository->find(hex2bin($id));

        if (!$merchant) {
            return $this->json(['error' => 'Merchant not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus) {
            return $this->json(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $statusEnum = MerchantStatus::from($newStatus);
            $merchant->setStatus($statusEnum);
            $merchant->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Merchant status updated successfully',
                'id' => bin2hex($merchant->getId()),
                'status' => $merchant->getStatus()->value,
            ]);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid status value',
                'validStatuses' => array_map(fn($case) => $case->value, MerchantStatus::cases()),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $merchant = $this->merchantRepository->find(hex2bin($id));

        if (!$merchant) {
            return $this->json(['error' => 'Merchant not found'], Response::HTTP_NOT_FOUND);
        }

        // Soft delete by setting status to inactive
        $merchant->setStatus(MerchantStatus::INACTIVE);
        $merchant->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Merchant deleted successfully',
            'id' => bin2hex($merchant->getId()),
        ]);
    }
}

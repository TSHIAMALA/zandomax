<?php

namespace App\Controller\Api;

use App\Entity\Space;
use App\Enum\SpaceStatus;
use App\Repository\SpaceRepository;
use App\Repository\SpaceCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/spaces', name: 'api_spaces_')]
class SpaceController extends AbstractController
{
    public function __construct(
        private SpaceRepository $spaceRepository,
        private SpaceCategoryRepository $spaceCategoryRepository,
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
        $zone = $request->query->get('zone');
        $categoryId = $request->query->get('category_id');
        $search = $request->query->get('search');

        // Build query
        $qb = $this->spaceRepository->createQueryBuilder('s')
            ->leftJoin('s.spaceCategory', 'sc')
            ->addSelect('sc');

        // Apply filters
        if ($status) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $status);
        }

        if ($zone) {
            $qb->andWhere('s.zone = :zone')
               ->setParameter('zone', $zone);
        }

        if ($categoryId) {
            $qb->andWhere('sc.id = :categoryId')
               ->setParameter('categoryId', hex2bin($categoryId));
        }

        if ($search) {
            $qb->andWhere('s.code LIKE :search OR s.zone LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Get total count
        $totalCount = (clone $qb)->select('COUNT(s.id)')->getQuery()->getSingleScalarResult();

        // Get paginated results
        $spaces = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('s.code', 'ASC')
            ->getQuery()
            ->getResult();

        // Serialize spaces
        $data = array_map(function (Space $space) {
            return [
                'id' => bin2hex($space->getId()),
                'code' => $space->getCode(),
                'zone' => $space->getZone(),
                'status' => $space->getStatus()->value,
                'category' => [
                    'id' => bin2hex($space->getSpaceCategory()->getId()),
                    'name' => $space->getSpaceCategory()->getName(),
                ],
                'architectureCoord' => $space->getArchitectureCoord(),
            ];
        }, $spaces);

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

        if (!isset($data['code']) || !isset($data['zone']) || !isset($data['category_id'])) {
            return $this->json([
                'error' => 'Missing required fields: code, zone, category_id'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = $this->spaceCategoryRepository->find(hex2bin($data['category_id']));
        if (!$category) {
            return $this->json(['error' => 'Invalid category'], Response::HTTP_BAD_REQUEST);
        }

        $space = new Space();
        $space->setCode($data['code']);
        $space->setZone($data['zone']);
        $space->setSpaceCategory($category);
        
        if (isset($data['architecture_coord'])) {
            $space->setArchitectureCoord($data['architecture_coord']);
        }

        if (isset($data['status'])) {
            try {
                $space->setStatus(SpaceStatus::from($data['status']));
            } catch (\ValueError $e) {
                return $this->json([
                    'error' => 'Invalid status value',
                    'validStatuses' => array_map(fn($case) => $case->value, SpaceStatus::cases()),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate
        $errors = $this->validator->validate($space);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($space);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Space created successfully',
            'id' => bin2hex($space->getId()),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $space = $this->spaceRepository->find(hex2bin($id));

        if (!$space) {
            return $this->json(['error' => 'Space not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => bin2hex($space->getId()),
            'code' => $space->getCode(),
            'zone' => $space->getZone(),
            'status' => $space->getStatus()->value,
            'category' => [
                'id' => bin2hex($space->getSpaceCategory()->getId()),
                'name' => $space->getSpaceCategory()->getName(),
            ],
            'architectureCoord' => $space->getArchitectureCoord(),
        ];

        return $this->json($data);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $space = $this->spaceRepository->find(hex2bin($id));

        if (!$space) {
            return $this->json(['error' => 'Space not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Update allowed fields
        if (isset($data['code'])) {
            $space->setCode($data['code']);
        }
        if (isset($data['zone'])) {
            $space->setZone($data['zone']);
        }
        if (isset($data['architecture_coord'])) {
            $space->setArchitectureCoord($data['architecture_coord']);
        }
        if (isset($data['category_id'])) {
            $category = $this->spaceCategoryRepository->find(hex2bin($data['category_id']));
            if ($category) {
                $space->setSpaceCategory($category);
            }
        }

        // Validate
        $errors = $this->validator->validate($space);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Space updated successfully',
            'id' => bin2hex($space->getId()),
        ]);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PUT'])]
    public function updateStatus(string $id, Request $request): JsonResponse
    {
        $space = $this->spaceRepository->find(hex2bin($id));

        if (!$space) {
            return $this->json(['error' => 'Space not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus) {
            return $this->json(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $statusEnum = SpaceStatus::from($newStatus);
            $space->setStatus($statusEnum);

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Space status updated successfully',
                'id' => bin2hex($space->getId()),
                'status' => $space->getStatus()->value,
            ]);
        } catch (\ValueError $e) {
            return $this->json([
                'error' => 'Invalid status value',
                'validStatuses' => array_map(fn($case) => $case->value, SpaceStatus::cases()),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $space = $this->spaceRepository->find(hex2bin($id));

        if (!$space) {
            return $this->json(['error' => 'Space not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if space is occupied
        if ($space->getStatus() === SpaceStatus::OCCUPIED) {
            return $this->json([
                'error' => 'Cannot delete occupied space'
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($space);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Space deleted successfully',
            'id' => bin2hex($space->getId()),
        ]);
    }
}

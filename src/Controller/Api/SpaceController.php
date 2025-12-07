<?php

namespace App\Controller\Api;

use App\Entity\Space;
use App\Enum\SpaceStatus;
use App\Repository\SpaceRepository;
use App\Service\PricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/spaces')]
class SpaceController extends AbstractController
{
    public function __construct(
        private SpaceRepository $spaceRepository,
        private PricingService $pricingService,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/available', name: 'api_spaces_available', methods: ['GET'])]
    public function getAvailable(Request $request): JsonResponse
    {
        $qb = $this->spaceRepository->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.isDeleted = :deleted')
            ->setParameter('status', SpaceStatus::AVAILABLE)
            ->setParameter('deleted', false);

        // Filter by zone
        if ($zone = $request->query->get('zone')) {
            $qb->andWhere('s.zone = :zone')
               ->setParameter('zone', $zone);
        }

        // Filter by space type
        if ($spaceTypeId = $request->query->get('space_type_id')) {
            $qb->andWhere('s.spaceType = :spaceType')
               ->setParameter('spaceType', $spaceTypeId);
        }

        // Filter by category
        if ($categoryId = $request->query->get('category_id')) {
            $qb->andWhere('s.spaceCategory = :category')
               ->setParameter('category', $categoryId);
        }

        $spaces = $qb->getQuery()->getResult();
        $json = $this->serializer->serialize($spaces, 'json', ['groups' => 'space:read']);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}/pricing', name: 'api_spaces_pricing', methods: ['GET'])]
    public function getPricing(Space $space): JsonResponse
    {
        $periodicities = $this->pricingService->getAvailablePeriodicitiesForSpace($space);
        
        return new JsonResponse([
            'space_id' => $space->getId(),
            'space_code' => $space->getCode(),
            'pricing' => $periodicities
        ]);
    }

    #[Route('/{id}/qrcode', name: 'api_spaces_qrcode', methods: ['GET'])]
    public function getQRCode(Space $space): JsonResponse
    {
        // For now, return QR code data URL
        // In production, use a QR code library like endroid/qr-code
        $qrData = json_encode([
            'space_id' => $space->getId(),
            'code' => $space->getCode(),
            'zone' => $space->getZone(),
        ]);

        return new JsonResponse([
            'space_code' => $space->getCode(),
            'qr_data' => $qrData,
            'message' => 'Use a QR code library to generate the actual QR code from qr_data'
        ]);
    }
}

<?php

namespace App\Controller\Api;

use App\Entity\Merchant;
use App\Entity\Space;
use App\Entity\SpaceReservation;
use App\Enum\Periodicity;
use App\Service\SpaceReservationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/reservations')]
class SpaceReservationController extends AbstractController
{
    public function __construct(
        private SpaceReservationService $reservationService,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'api_reservations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $merchant = $this->entityManager->getRepository(Merchant::class)->find($data['merchant_id']);
            $space = $this->entityManager->getRepository(Space::class)->find($data['space_id']);

            if (!$merchant || !$space) {
                return new JsonResponse(['error' => 'Merchant or Space not found'], Response::HTTP_NOT_FOUND);
            }

            $periodicity = Periodicity::from($data['periodicity']);
            $duration = (int) $data['duration'];

            $reservation = $this->reservationService->createReservation(
                $merchant,
                $space,
                $periodicity,
                $duration
            );

            $json = $this->serializer->serialize($reservation, 'json', ['groups' => 'reservation:read']);
            
            return new JsonResponse($json, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/pending', name: 'api_reservations_pending', methods: ['GET'])]
    public function getPending(): JsonResponse
    {
        $reservations = $this->reservationService->getPendingReservations();
        $json = $this->serializer->serialize($reservations, 'json', ['groups' => 'reservation:read']);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}/approve', name: 'api_reservations_approve', methods: ['PATCH'])]
    public function approve(SpaceReservation $reservation): JsonResponse
    {
        try {
            $this->reservationService->approveReservation($reservation);
            
            return new JsonResponse(['message' => 'Reservation approved successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/reject', name: 'api_reservations_reject', methods: ['PATCH'])]
    public function reject(SpaceReservation $reservation, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'No reason provided';

        try {
            $this->reservationService->rejectReservation($reservation, $reason);
            
            return new JsonResponse(['message' => 'Reservation rejected successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/cancel', name: 'api_reservations_cancel', methods: ['PATCH'])]
    public function cancel(SpaceReservation $reservation): JsonResponse
    {
        try {
            $this->reservationService->cancelReservation($reservation);
            
            return new JsonResponse(['message' => 'Reservation cancelled successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/merchant/{id}', name: 'api_reservations_by_merchant', methods: ['GET'])]
    public function getByMerchant(Merchant $merchant): JsonResponse
    {
        $reservations = $this->reservationService->getMerchantReservations($merchant);
        $json = $this->serializer->serialize($reservations, 'json', ['groups' => 'reservation:read']);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}

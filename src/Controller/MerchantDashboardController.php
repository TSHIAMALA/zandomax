<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\SpaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/merchant', name: 'merchant_')]
#[IsGranted('ROLE_MERCHANT')]
class MerchantDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        ReservationRepository $reservationRepository,
        SpaceRepository $spaceRepository
    ): Response {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }

        // Récupérer les réservations du marchand
        $reservations = $reservationRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC'],
            5
        );

        // Récupérer les espaces disponibles
        $availableSpaces = $spaceRepository->findBy(
            ['status' => \App\Enum\SpaceStatus::AVAILABLE, 'isDeleted' => false],
            ['code' => 'ASC'],
            10
        );

        return $this->render('merchant/dashboard.html.twig', [
            'merchant' => $merchant,
            'reservations' => $reservations,
            'availableSpaces' => $availableSpaces,
        ]);
    }

    #[Route('/spaces', name: 'spaces')]
    public function spaces(SpaceRepository $spaceRepository): Response
    {
        $spaces = $spaceRepository->findBy(
            ['status' => \App\Enum\SpaceStatus::AVAILABLE, 'isDeleted' => false],
            ['zone' => 'ASC', 'code' => 'ASC']
        );

        return $this->render('merchant/spaces.html.twig', [
            'spaces' => $spaces,
        ]);
    }

    #[Route('/reservations', name: 'reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $reservations = $reservationRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC']
        );

        return $this->render('merchant/reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Repository\SpaceReservationRepository;
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
        SpaceReservationRepository $reservationRepository,
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
    public function reservations(SpaceReservationRepository $reservationRepository): Response
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

    #[Route('/space/{id}/reserve', name: 'space_reserve')]
    public function reserveSpace(string $id, SpaceRepository $spaceRepository): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }

        // Convertir l'ID hexadécimal en binaire
        $binaryId = hex2bin($id);
        $space = $spaceRepository->find($binaryId);

        if (!$space || $space->isDeleted() || $space->getStatus() !== \App\Enum\SpaceStatus::AVAILABLE) {
            $this->addFlash('error', 'Cet espace n\'est pas disponible.');
            return $this->redirectToRoute('merchant_spaces');
        }

        return $this->render('merchant/reserve_space.html.twig', [
            'space' => $space,
            'merchant' => $merchant,
        ]);
    }

    #[Route('/space/{id}/reserve/submit', name: 'space_reserve_submit', methods: ['POST'])]
    public function submitReservation(
        string $id,
        SpaceRepository $spaceRepository,
        SpaceReservationRepository $reservationRepository,
        \Symfony\Component\HttpFoundation\Request $request,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        if (!$merchant) {
            throw $this->createNotFoundException('Marchand non trouvé');
        }

        // Convertir l'ID hexadécimal en binaire
        $binaryId = hex2bin($id);
        $space = $spaceRepository->find($binaryId);

        if (!$space || $space->isDeleted() || $space->getStatus() !== \App\Enum\SpaceStatus::AVAILABLE) {
            $this->addFlash('error', 'Cet espace n\'est pas disponible.');
            return $this->redirectToRoute('merchant_spaces');
        }

        // Créer la réservation
        $reservation = new \App\Entity\SpaceReservation();
        $reservation->setMerchant($merchant);
        $reservation->setSpace($space);
        
        $periodicity = $request->request->get('periodicity');
        $reservation->setPeriodicity(\App\Enum\Periodicity::from($periodicity));
        
        $duration = (int) $request->request->get('duration');
        $reservation->setDuration($duration);
        
        $firstPaymentAmount = $request->request->get('first_payment_amount');
        if ($firstPaymentAmount) {
            $reservation->setFirstPaymentAmount($firstPaymentAmount);
        }
        
        $reservation->setStatus(\App\Enum\ReservationStatus::PENDING_ADMIN);

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de réservation a été soumise avec succès. Elle sera examinée par l\'administrateur.');
        
        return $this->redirectToRoute('merchant_reservations');
    }
}

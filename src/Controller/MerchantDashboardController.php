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
        SpaceRepository $spaceRepository,
        \App\Repository\NotificationRepository $notificationRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager
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

        // Récupérer les notifications non lues du marchand
        $unreadNotifications = $notificationRepository->findBy(
            ['merchant' => $merchant, 'isRead' => false, 'isForAdmin' => false],
            ['createdAt' => 'DESC'],
            5
        );

        // Marquer les notifications d'activation comme lues après affichage
        foreach ($unreadNotifications as $notification) {
            if ($notification->getType() === 'account_activated') {
                $notification->setIsRead(true);
                $notification->setReadAt(new \DateTime());
            }
        }
        $entityManager->flush();

        return $this->render('merchant/dashboard.html.twig', [
            'merchant' => $merchant,
            'reservations' => $reservations,
            'availableSpaces' => $availableSpaces,
            'notifications' => $unreadNotifications,
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

    #[Route('/reservations/{id}', name: 'reservation_show')]
    public function showReservation(
        string $id, 
        SpaceReservationRepository $reservationRepository,
        \App\Repository\PaymentRepository $paymentRepository
    ): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $binaryId = hex2bin($id);
        $reservation = $reservationRepository->find($binaryId);

        if (!$reservation || $reservation->getMerchant()->getId() !== $merchant->getId()) {
            $this->addFlash('error', 'Réservation non trouvée.');
            return $this->redirectToRoute('merchant_reservations');
        }

        // Vérifier si un paiement existe déjà pour ce marchand
        $existingPayments = $paymentRepository->findBy(['merchant' => $merchant], ['createdAt' => 'DESC']);
        $hasPendingPayment = false;
        $hasPaidPayment = false;
        
        foreach ($existingPayments as $payment) {
            if ($payment->getStatus()->value === 'pending') {
                $hasPendingPayment = true;
            }
            if (in_array($payment->getStatus()->value, ['paid', 'completed'])) {
                $hasPaidPayment = true;
            }
        }

        return $this->render('merchant/reservation_show.html.twig', [
            'reservation' => $reservation,
            'hasPendingPayment' => $hasPendingPayment,
            'hasPaidPayment' => $hasPaidPayment,
        ]);
    }

    #[Route('/payments', name: 'payments')]
    public function payments(\App\Repository\PaymentRepository $paymentRepository): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $payments = $paymentRepository->findBy(
            ['merchant' => $merchant],
            ['createdAt' => 'DESC']
        );

        return $this->render('merchant/payments.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/reservations/{id}/pay', name: 'reservation_pay')]
    public function payReservation(
        string $id, 
        SpaceReservationRepository $reservationRepository
    ): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $binaryId = hex2bin($id);
        $reservation = $reservationRepository->find($binaryId);

        if (!$reservation || $reservation->getMerchant()->getId() !== $merchant->getId()) {
            $this->addFlash('error', 'Réservation non trouvée.');
            return $this->redirectToRoute('merchant_reservations');
        }

        if ($reservation->getStatus()->value !== 'approved') {
            $this->addFlash('error', 'Cette réservation ne peut pas être payée.');
            return $this->redirectToRoute('merchant_reservations');
        }

        return $this->render('merchant/reservation_pay.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservations/{id}/pay/process', name: 'reservation_pay_process', methods: ['POST'])]
    public function processPayment(
        string $id,
        SpaceReservationRepository $reservationRepository,
        \App\Repository\PaymentRepository $paymentRepository,
        \App\Service\NotificationService $notificationService,
        \Symfony\Component\HttpFoundation\Request $request,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();
        $merchant = $user->getMerchant();

        $binaryId = hex2bin($id);
        $reservation = $reservationRepository->find($binaryId);

        if (!$reservation || $reservation->getMerchant()->getId() !== $merchant->getId()) {
            $this->addFlash('error', 'Réservation non trouvée.');
            return $this->redirectToRoute('merchant_reservations');
        }

        if ($reservation->getStatus()->value !== 'approved') {
            $this->addFlash('error', 'Cette réservation ne peut pas être payée.');
            return $this->redirectToRoute('merchant_reservations');
        }

        $paymentMethod = $request->request->get('payment_method');
        $transactionRef = $request->request->get('transaction_ref');

        // Créer le paiement
        $payment = new \App\Entity\Payment();
        $payment->setMerchant($merchant);
        $payment->setAmount($reservation->getFirstPaymentAmount());
        $payment->setCurrency($reservation->getCurrency()?->getCode() ?? 'CDF');
        $payment->setType(\App\Enum\PaymentType::RESERVATION);
        $payment->setStatus(\App\Enum\PaymentStatus::PENDING);
        $payment->setInitiatedBy($user);
        
        if ($transactionRef) {
            $payment->setBankingTransactionId($transactionRef);
        }

        $entityManager->persist($payment);
        
        // Mettre à jour le moyen de paiement de la réservation si changé
        if ($paymentMethod) {
            $reservation->setPaymentMethod(\App\Enum\PaymentMethod::from($paymentMethod));
        }

        $entityManager->flush();

        // Notifier l'admin du nouveau paiement
        $notificationService->createAdminNotification(
            'new_payment',
            'Nouveau paiement en attente',
            sprintf(
                '%s %s a effectué un paiement de %s %s pour l\'espace %s.',
                $merchant->getFirstname(),
                $merchant->getLastname(),
                number_format((float)$payment->getAmount(), 0, ',', ' '),
                $payment->getCurrency(),
                $reservation->getSpace()->getCode()
            ),
            $merchant,
            [
                'payment_id' => $payment->getId(),
                'amount' => $payment->getAmount(),
                'space_code' => $reservation->getSpace()->getCode()
            ]
        );

        $this->addFlash('success', 'Votre paiement a été enregistré et est en attente de validation par l\'administrateur.');
        
        return $this->redirectToRoute('merchant_reservations');
    }

    #[Route('/space/{id}/reserve', name: 'space_reserve')]
    public function reserveSpace(
        string $id, 
        SpaceRepository $spaceRepository,
        \App\Repository\PricingRuleRepository $pricingRuleRepository
    ): Response
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

        // Récupérer les règles de prix actives pour cet espace
        $pricingRules = $pricingRuleRepository->findActiveBySpace($space);

        return $this->render('merchant/reserve_space.html.twig', [
            'space' => $space,
            'merchant' => $merchant,
            'pricingRules' => $pricingRules,
        ]);
    }

    #[Route('/space/{id}/reserve/submit', name: 'space_reserve_submit', methods: ['POST'])]
    public function submitReservation(
        string $id,
        SpaceRepository $spaceRepository,
        SpaceReservationRepository $reservationRepository,
        \App\Repository\PricingRuleRepository $pricingRuleRepository,
        \App\Repository\CurrencyRepository $currencyRepository,
        \App\Service\NotificationService $notificationService,
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
        $periodicityEnum = \App\Enum\Periodicity::from($periodicity);
        $reservation->setPeriodicity($periodicityEnum);
        
        $duration = (int) $request->request->get('duration');
        $reservation->setDuration($duration);
        
        // Récupérer le prix unitaire depuis les règles de prix
        $pricingRule = $pricingRuleRepository->findBySpaceAndPeriodicity($space, $periodicityEnum);
        if ($pricingRule) {
            $unitPrice = $pricingRule->getPrice();
            $reservation->setUnitPrice($unitPrice);
            $totalAmount = (float)$unitPrice * $duration;
            $reservation->setFirstPaymentAmount(number_format($totalAmount, 2, '.', ''));
            
            // Récupérer la devise
            $currency = $pricingRule->getCurrency() ?? $currencyRepository->findOneBy(['code' => 'CDF']);
            $reservation->setCurrency($currency);
        }
        
        // Moyen de paiement
        $paymentMethod = $request->request->get('payment_method');
        if ($paymentMethod) {
            $reservation->setPaymentMethod(\App\Enum\PaymentMethod::from($paymentMethod));
        }
        
        $reservation->setStatus(\App\Enum\ReservationStatus::PENDING_ADMIN);

        $entityManager->persist($reservation);
        $entityManager->flush();

        // Créer une notification pour l'admin
        $notificationService->createAdminNotification(
            'new_reservation',
            'Nouvelle demande de réservation',
            sprintf(
                '%s %s a demandé la réservation de l\'espace %s pour %d %s.',
                $merchant->getFirstname(),
                $merchant->getLastname(),
                $space->getCode(),
                $duration,
                $periodicityEnum->getLabel()
            ),
            $merchant,
            [
                'reservation_id' => bin2hex($reservation->getId()),
                'space_code' => $space->getCode(),
                'amount' => $reservation->getFirstPaymentAmount()
            ]
        );

        $this->addFlash('success', 'Votre demande de réservation a été soumise avec succès. Elle sera examinée par l\'administrateur.');
        
        return $this->redirectToRoute('merchant_reservations');
    }
}

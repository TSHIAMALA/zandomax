<?php

namespace App\Controller\MarketAdmin;

use App\Entity\SpaceReservation;
use App\Entity\Contract;
use App\Enum\ReservationStatus;
use App\Repository\SpaceReservationRepository;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use App\Service\NotificationService;

#[Route('/admin/reservations', name: 'market_admin_reservations_')]
#[IsGranted('ROLE_MARKET_ADMIN')]
class ReservationController extends AbstractController
{
    public function __construct(
        private SpaceReservationRepository $reservationRepository,
        private ContractRepository $contractRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $status = $request->query->get('status');
        $search = $request->query->get('search');

        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.merchant', 'm')
            ->leftJoin('r.space', 's')
            ->leftJoin('s.spaceCategory', 'sc')
            ->addSelect('m', 's', 'sc')
            ->where('r.isDeleted = :deleted')
            ->setParameter('deleted', false);

        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        if ($search) {
            $qb->andWhere('m.firstname LIKE :search OR m.lastname LIKE :search OR s.code LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $totalCount = (clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $reservations = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('market_admin/reservations/index.html.twig', [
            'reservations' => $reservations,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(string $id): Response
    {
        $reservation = $this->reservationRepository->find(hex2bin($id));

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        return $this->render('market_admin/reservations/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(string $id): Response
    {
        $reservation = $this->reservationRepository->find(hex2bin($id));

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        if ($reservation->getStatus() !== ReservationStatus::PENDING_ADMIN) {
            $this->addFlash('error', 'Cette réservation ne peut plus être approuvée');
            return $this->redirectToRoute('market_admin_reservations_show', ['id' => $id]);
        }

        // Créer le contrat automatiquement
        $contract = $this->createContractFromReservation($reservation);
        
        // Mettre à jour le statut de la réservation
        $reservation->setStatus(ReservationStatus::APPROVED);
        
        // Mettre à jour le statut de l'espace
        $space = $reservation->getSpace();
        $space->setStatus('occupied');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();
        
        // Envoyer notification email
        $this->notificationService->notifyReservationApproved($reservation, $contract);

        $this->addFlash('success', 'Réservation approuvée et contrat généré avec succès');

        return $this->redirectToRoute('market_admin_contracts_show', [
            'id' => bin2hex($contract->getId())
        ]);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(string $id, Request $request): Response
    {
        $reservation = $this->reservationRepository->find(hex2bin($id));

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        if ($reservation->getStatus() !== ReservationStatus::PENDING_ADMIN) {
            $this->addFlash('error', 'Cette réservation ne peut plus être rejetée');
            return $this->redirectToRoute('market_admin_reservations_show', ['id' => $id]);
        }

        $reason = $request->request->get('rejection_reason');
        
        if (empty($reason)) {
            $this->addFlash('error', 'Le motif de refus est obligatoire');
            return $this->redirectToRoute('market_admin_reservations_show', ['id' => $id]);
        }

        $reservation->setStatus(ReservationStatus::REJECTED);
        $reservation->setRejectionReason($reason);

        // Libérer l'espace
        $space = $reservation->getSpace();
        if ($space->getStatus() === 'reserved') {
            $space->setStatus('available');
        }

        $this->entityManager->flush();
        
        // Envoyer notification email
        $this->notificationService->notifyReservationRejected($reservation);
        
        // Envoyer notification email
        $this->notificationService->notifyReservationApproved($reservation, $contract);

        $this->addFlash('success', 'Réservation rejetée avec succès');

        return $this->redirectToRoute('market_admin_reservations_index');
    }

    private function createContractFromReservation(SpaceReservation $reservation): Contract
    {
        $contract = new Contract();
        $contract->setMerchant($reservation->getMerchant());
        $contract->setSpace($reservation->getSpace());
        
        // Générer le code du contrat
        $year = date('Y');
        $lastContract = $this->contractRepository->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        $number = 1;
        if ($lastContract && $lastContract->getContractCode()) {
            preg_match('/CTR-\d{4}-(\d+)/', $lastContract->getContractCode(), $matches);
            if (isset($matches[1])) {
                $number = (int)$matches[1] + 1;
            }
        }
        
        $contract->setContractCode(sprintf('CTR-%s-%04d', $year, $number));
        
        // Calculer les dates
        $startDate = new \DateTime();
        $endDate = clone $startDate;
        
        switch ($reservation->getPeriodicity()->value) {
            case 'day':
                $endDate->modify('+' . $reservation->getDuration() . ' days');
                break;
            case 'week':
                $endDate->modify('+' . ($reservation->getDuration() * 7) . ' days');
                break;
            case 'month':
                $endDate->modify('+' . $reservation->getDuration() . ' months');
                break;
            case 'quarter':
                $endDate->modify('+' . ($reservation->getDuration() * 3) . ' months');
                break;
            case 'semester':
                $endDate->modify('+' . ($reservation->getDuration() * 6) . ' months');
                break;
            case 'year':
                $endDate->modify('+' . $reservation->getDuration() . ' years');
                break;
        }
        
        $contract->setStartDate($startDate);
        $contract->setEndDate($endDate);
        $contract->setRentAmount($reservation->getFirstPaymentAmount());
        $contract->setGuaranteeAmount((float)$reservation->getFirstPaymentAmount() * 2); // 2 mois de garantie
        $contract->setCurrency($reservation->getCurrency());
        $contract->setStatus('active');

        return $contract;
    }
}

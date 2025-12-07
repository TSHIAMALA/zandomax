<?php

namespace App\Repository;

use App\Entity\Merchant;
use App\Entity\SpaceReservation;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpaceReservation>
 */
class SpaceReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpaceReservation::class);
    }

    public function findPendingReservations(): array
    {
        return $this->createQueryBuilder('sr')
            ->where('sr.status = :status')
            ->andWhere('sr.isDeleted = :deleted')
            ->setParameter('status', ReservationStatus::PENDING_ADMIN)
            ->setParameter('deleted', false)
            ->orderBy('sr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByMerchant(Merchant $merchant): array
    {
        return $this->createQueryBuilder('sr')
            ->where('sr.merchant = :merchant')
            ->andWhere('sr.isDeleted = :deleted')
            ->setParameter('merchant', $merchant)
            ->setParameter('deleted', false)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findApprovedByMerchant(Merchant $merchant): array
    {
        return $this->createQueryBuilder('sr')
            ->where('sr.merchant = :merchant')
            ->andWhere('sr.status = :status')
            ->andWhere('sr.isDeleted = :deleted')
            ->setParameter('merchant', $merchant)
            ->setParameter('status', ReservationStatus::APPROVED)
            ->setParameter('deleted', false)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Merchant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findPendingNotifications(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.isSent = :sent')
            ->setParameter('sent', false)
            ->orderBy('n.createdAt', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    public function findUnreadByMerchant(Merchant $merchant): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.merchant = :merchant')
            ->andWhere('n.isRead = :read')
            ->andWhere('n.isSent = :sent')
            ->setParameter('merchant', $merchant)
            ->setParameter('read', false)
            ->setParameter('sent', true)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByMerchant(Merchant $merchant): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.merchant = :merchant')
            ->andWhere('n.isRead = :read')
            ->andWhere('n.isSent = :sent')
            ->setParameter('merchant', $merchant)
            ->setParameter('read', false)
            ->setParameter('sent', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

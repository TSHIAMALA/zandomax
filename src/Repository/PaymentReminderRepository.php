<?php
namespace App\Repository;

use App\Entity\PaymentReminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentReminder::class);
    }

    public function findPendingReminders(): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.isSent = :sent')
            ->andWhere('pr.scheduledAt <= :now')
            ->setParameter('sent', false)
            ->setParameter('now', new \DateTime())
            ->orderBy('pr.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\PricingRule;
use App\Entity\Space;
use App\Enum\Periodicity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PricingRule>
 */
class PricingRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PricingRule::class);
    }

    public function findBySpaceAndPeriodicity(Space $space, Periodicity $periodicity): ?PricingRule
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.space = :space')
            ->andWhere('pr.periodicity = :periodicity')
            ->andWhere('pr.isDeleted = :deleted')
            ->andWhere('pr.isActive = :active')
            ->setParameter('space', $space)
            ->setParameter('periodicity', $periodicity)
            ->setParameter('deleted', false)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveBySpace(Space $space): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.space = :space')
            ->andWhere('pr.isDeleted = :deleted')
            ->andWhere('pr.isActive = :active')
            ->setParameter('space', $space)
            ->setParameter('deleted', false)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}

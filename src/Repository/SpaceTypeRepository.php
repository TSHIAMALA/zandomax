<?php

namespace App\Repository;

use App\Entity\SpaceType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpaceType>
 */
class SpaceTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpaceType::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('st')
            ->where('st.isDeleted = :deleted')
            ->setParameter('deleted', false)
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Service;

use App\Entity\Space;
use App\Enum\SpaceStatus;
use Doctrine\ORM\EntityManagerInterface;

class SpaceAllocationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function isSpaceAvailable(Space $space): bool
    {
        return $space->getStatus() === SpaceStatus::AVAILABLE;
    }

    public function allocateSpace(Space $space): void
    {
        if (!$this->isSpaceAvailable($space)) {
            throw new \RuntimeException(sprintf('Space %s is not available for allocation.', $space->getCode()));
        }

        $space->setStatus(SpaceStatus::OCCUPIED);
        $this->entityManager->flush();
    }

    public function releaseSpace(Space $space): void
    {
        $space->setStatus(SpaceStatus::AVAILABLE);
        $this->entityManager->flush();
    }

    public function markAsMaintenance(Space $space): void
    {
        $space->setStatus(SpaceStatus::MAINTENANCE);
        $this->entityManager->flush();
    }
}

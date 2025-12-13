<?php

namespace App\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BinaryUuidToEntityTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $entityClass
    ) {
    }

    /**
     * Transforme une entité en ID hexadécimal pour l'affichage
     */
    public function transform($entity): mixed
    {
        if (null === $entity) {
            return '';
        }

        return bin2hex($entity->getId());
    }

    /**
     * Transforme un ID hexadécimal en entité
     */
    public function reverseTransform($hexId): mixed
    {
        if (!$hexId) {
            return null;
        }

        try {
            $binaryId = hex2bin($hexId);
            $entity = $this->em->getRepository($this->entityClass)->find($binaryId);

            if (null === $entity) {
                throw new TransformationFailedException(sprintf(
                    'Une entité avec l\'ID "%s" n\'existe pas!',
                    $hexId
                ));
            }

            return $entity;
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage());
        }
    }
}

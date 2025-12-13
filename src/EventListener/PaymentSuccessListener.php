<?php

namespace App\EventListener;

use App\Entity\Transaction;
use App\Enum\MerchantStatus;
use App\Enum\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, entity: Transaction::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Transaction::class)]
class PaymentSuccessListener
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function postPersist(Transaction $transaction, LifecycleEventArgs $event): void
    {
        $this->validateMerchantAfterPayment($transaction);
    }

    public function postUpdate(Transaction $transaction, LifecycleEventArgs $event): void
    {
        $this->validateMerchantAfterPayment($transaction);
    }

    private function validateMerchantAfterPayment(Transaction $transaction): void
    {
        // Vérifier si c'est un paiement réussi
        if ($transaction->getType() !== TransactionType::PAYMENT) {
            return;
        }

        if ($transaction->getStatus() !== 'completed') {
            return;
        }

        // Récupérer le marchand
        $merchant = $transaction->getMerchant();
        
        if (!$merchant) {
            return;
        }

        // Si le marchand est en attente de validation, l'activer
        if ($merchant->getStatus() === MerchantStatus::PENDING_VALIDATION) {
            $merchant->setStatus(MerchantStatus::ACTIVE);
            $this->em->flush();
            
            // TODO: Envoyer une notification au marchand
            // TODO: Créer une notification pour l'admin
        }
    }
}

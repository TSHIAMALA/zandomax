<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\MerchantStatus;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class MerchantAccountChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si l'utilisateur a un marchand associé
        $merchant = $user->getMerchant();
        if (!$merchant) {
            return; // Pas un marchand, probablement un admin
        }

        // Vérifier le statut du marchand
        if ($merchant->getStatus() === MerchantStatus::PENDING_VALIDATION) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est en attente de validation par l\'administrateur. Vous recevrez une notification une fois votre compte activé.'
            );
        }

        if ($merchant->getStatus() === MerchantStatus::SUSPENDED) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été suspendu. Veuillez contacter l\'administrateur pour plus d\'informations.'
            );
        }

        if ($merchant->getStatus() === MerchantStatus::BLACKLISTED) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été bloqué. Veuillez contacter l\'administrateur.'
            );
        }

        if ($merchant->getStatus() === MerchantStatus::INACTIVE) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est inactif. Veuillez contacter l\'administrateur pour le réactiver.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Rien à vérifier après l'authentification
    }
}

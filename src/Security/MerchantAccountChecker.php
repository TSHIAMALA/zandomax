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

        // PERMETTRE la connexion pour PENDING_VALIDATION
        // Le marchand pourra se connecter mais verra une notification sur son dashboard
        
        // Bloquer uniquement les statuts vraiment problématiques
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

        // INACTIVE peut se connecter aussi (compte dormant)
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Rien à vérifier après l'authentification
    }
}

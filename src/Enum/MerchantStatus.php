<?php

namespace App\Enum;

enum MerchantStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING_VALIDATION = 'pending_validation';
    case SUSPENDED = 'suspended';
    case BLACKLISTED = 'blacklisted';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
            self::PENDING_VALIDATION => 'En attente de validation',
            self::SUSPENDED => 'Suspendu',
            self::BLACKLISTED => 'Liste noire',
        };
    }
}

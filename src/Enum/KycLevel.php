<?php

namespace App\Enum;

enum KycLevel: string
{
    case BASIC = 'basic';
    case FULL = 'full';
    case VERIFIED = 'verified';

    public function label(): string
    {
        return match($this) {
            self::BASIC => 'Basique',
            self::FULL => 'Complet',
            self::VERIFIED => 'Vérifié',
        };
    }
}

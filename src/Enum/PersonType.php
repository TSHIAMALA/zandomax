<?php

namespace App\Enum;

enum PersonType: string
{
    case PHYSICAL = 'physical';
    case MORAL = 'moral';

    public function label(): string
    {
        return match($this) {
            self::PHYSICAL => 'Personne Physique',
            self::MORAL => 'Personne Morale',
        };
    }
}

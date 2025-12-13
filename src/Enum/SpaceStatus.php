<?php

namespace App\Enum;

enum SpaceStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';
    case RESERVED = 'reserved';

    public function label(): string
    {
        return match($this) {
            self::AVAILABLE => 'Disponible',
            self::OCCUPIED => 'Occupé',
            self::MAINTENANCE => 'En maintenance',
            self::RESERVED => 'Réservé',
        };
    }
}

<?php

namespace App\Enum;

enum ReservationStatus: string
{
    case PENDING_ADMIN = 'pending_admin';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING_ADMIN => 'En attente de validation',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
            self::CANCELLED => 'Annulé',
        };
    }
}

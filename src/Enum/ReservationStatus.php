<?php

namespace App\Enum;

enum ReservationStatus: string
{
    case PENDING_ADMIN = 'pending_admin';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}

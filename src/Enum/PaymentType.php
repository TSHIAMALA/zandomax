<?php

namespace App\Enum;

enum PaymentType: string
{
    case LOYER = 'loyer';
    case GARANTIE = 'garantie';
    case TAXE = 'taxe';
    case FRAIS_ADMIN = 'frais_admin';
    case RESERVATION = 'reservation';
}

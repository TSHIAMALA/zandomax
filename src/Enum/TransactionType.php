<?php

namespace App\Enum;

enum TransactionType: string
{
    case PAYMENT = 'payment';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';
    case FEE = 'fee';
}

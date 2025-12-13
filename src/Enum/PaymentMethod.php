<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case AIRTEL_MONEY = 'airtel_money';
    case MPESA = 'mpesa';
    case ORANGE_MONEY = 'orange_money';
    case CREDIT_CARD = 'credit_card';
}

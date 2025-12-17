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

    public function getLabel(): string
    {
        return match($this) {
            self::CASH => 'Espèces',
            self::BANK_TRANSFER => 'Virement bancaire',
            self::AIRTEL_MONEY => 'Airtel Money',
            self::MPESA => 'M-Pesa',
            self::ORANGE_MONEY => 'Orange Money',
            self::CREDIT_CARD => 'Carte bancaire',
        };
    }
}

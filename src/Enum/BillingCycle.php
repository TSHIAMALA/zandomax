<?php

namespace App\Enum;

enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case ONE_OFF = 'one_off';
}

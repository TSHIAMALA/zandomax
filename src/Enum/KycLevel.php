<?php

namespace App\Enum;

enum KycLevel: string
{
    case BASIC = 'basic';
    case FULL = 'full';
    case VERIFIED = 'verified';
}

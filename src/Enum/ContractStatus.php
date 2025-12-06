<?php

namespace App\Enum;

enum ContractStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';
    case PENDING_SIGNATURE = 'pending_signature';
}

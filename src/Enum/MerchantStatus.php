<?php

namespace App\Enum;

enum MerchantStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING_VALIDATION = 'pending_validation';
    case SUSPENDED = 'suspended';
    case BLACKLISTED = 'blacklisted';
}

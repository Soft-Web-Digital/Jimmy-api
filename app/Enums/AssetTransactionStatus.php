<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum AssetTransactionStatus: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case TRANSFERRED = 'transferred';
    case DECLINED = 'declined';
    case APPROVED = 'approved';
    case PARTIALLYAPPROVED = 'partially_approved';
}

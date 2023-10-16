<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum WalletTransactionStatus: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case CLOSED = 'closed';
    case DECLINED = 'declined';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
}

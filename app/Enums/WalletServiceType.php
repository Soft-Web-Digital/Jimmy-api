<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum WalletServiceType: string
{
    use EnumTrait;

    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case OTHER = 'other';
}

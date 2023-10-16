<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum AssetTransactionTradeType: string
{
    use EnumTrait;

    case BUY = 'buy';
    case SELL = 'sell';
}

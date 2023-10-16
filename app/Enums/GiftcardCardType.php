<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum GiftcardCardType: string
{
    use EnumTrait;

    case VIRTUAL = 'virtual';
    case PHYSICAL = 'physical';
}

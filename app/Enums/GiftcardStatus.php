<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum GiftcardStatus: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case DECLINED = 'declined';
    case APPROVED = 'approved';
    case PARTIALLYAPPROVED = 'partially_approved';
    case MULTIPLE = 'multiple';
}

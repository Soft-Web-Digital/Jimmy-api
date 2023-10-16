<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum AlertStatus: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case ONGOING = 'ongoing';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
}

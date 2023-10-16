<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum Queue: string
{
    use EnumTrait;

    case DEFAULT = 'default'; // should not be changed
    case CRITICAL = 'critical';
    case MAIL = 'mail';
}

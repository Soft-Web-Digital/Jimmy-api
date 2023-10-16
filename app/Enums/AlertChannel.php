<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum AlertChannel: string
{
    use EnumTrait;

    case EMAIL = 'email';
    case IN_APP = 'in_app';
    case PUSH = 'push';
}

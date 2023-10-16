<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;
use Illuminate\Database\Eloquent\Builder;

enum AlertTargetUser: string
{
    use EnumTrait;

    case ALL = 'all';
    case VERIFIED = 'verified';
    case SPECIFIC = 'specific';

    /**
     * Get the query.
     *
     * @param array<int, string>|null $ids
     * @return \Closure|null
     */
    public function query(array|null $ids = null): \Closure|null
    {
        return match ($this) {
            self::ALL => function (Builder $query) {
                return $query;
            },
            self::VERIFIED => function (Builder $query) {
                return $query->whereNotNull('email_verified_at');
            },
            self::SPECIFIC => function ($query) use ($ids) {
                return $query->whereIn('id', $ids);
            },
        };
    }
}

<?php

namespace App\Models;

use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\MassPrunable;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use UUID;
    use MorphMapTrait;
    use MassPrunable;

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        $period = now()->subMonths(3);

        return static::where('created_at', '<=', $period)->where(
            fn ($query) => $query->whereNull('last_used_at')->orWhere('last_used_at', '<=', $period)
        );
    }
}

<?php

namespace App\Models;

use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class EmailVerificationCode extends Model
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
        $period = now()->subMinutes(config('auth.verification.expire', 60));

        return static::where('created_at', '<=', $period);
    }

    /**
     * Get the code's expired status.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->created_at->diffInMinutes(now()) > config('auth.verification.expire', 60);
    }

    /**
     * Get the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}

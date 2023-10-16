<?php

namespace App\Models;

use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class TwoFaVerificationCode extends Model
{
    use UUID;
    use MorphMapTrait;
    use MassPrunable;

    public const EXPIRATION_TIME_IN_MINUTES = 5;

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        $period = now()->subMinutes(self::EXPIRATION_TIME_IN_MINUTES);

        return static::where('created_at', '<=', $period);
    }

    /**
     * Get the code's expired status.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->created_at->diffInMinutes(now()) > self::EXPIRATION_TIME_IN_MINUTES;
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

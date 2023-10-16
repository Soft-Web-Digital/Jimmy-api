<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait UUID
{
    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Hooks the UUID trait to model creating event.
     *
     * @return void
     */
    protected static function bootUUID()
    {
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = $model->{$model->getKeyName()} ?: Str::orderedUuid()->toString();
        });
    }
}

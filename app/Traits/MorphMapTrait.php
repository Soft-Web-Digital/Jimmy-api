<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait MorphMapTrait
{
    /**
     * Get the class name for polymorphic relations.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return Str::snake((new \ReflectionClass($this))->getShortName());
    }
}

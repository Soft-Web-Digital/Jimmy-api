<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum DatatypeEnum: string
{
    use EnumTrait;

    case PERCENTAGE = 'percentage';
    case NUMERIC = 'numeric';
    case TEXT = 'text';

    /**
     * Get the hint.
     *
     * @return string
     */
    public function hint(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'percentage',
            self::NUMERIC => 'any numeric value',
            self::TEXT => 'text, max: 2000',
        };
    }

    /**
     * Get the developer hint.
     *
     * @return string
     */
    public function developerHint(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'any numeric value',
            self::NUMERIC => 'number between 0 and 100',
            self::TEXT => 'text below 2000 characters',
        };
    }

    /**
     * Get the Laravel ruleset.
     *
     * @return string
     */
    public function rule(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'numeric|between:0,100',
            self::NUMERIC => 'numeric',
            self::TEXT => 'string|max:2000',
        };
    }
}

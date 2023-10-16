<?php

namespace App\Traits;

trait EnumTrait
{
    /**
     * Get the enum values.
     *
     * @return array<int, mixed>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the enum value pair.
     *
     * @return array<mixed, string>
     */
    public static function valuePair(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'name')
        );
    }

    /**
     * Get a random enum value.
     *
     * @return static
     */
    public static function random(): static
    {
        $enums = self::cases();

        return $enums[array_rand($enums)];
    }
}

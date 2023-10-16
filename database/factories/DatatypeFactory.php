<?php

namespace Database\Factories;

use App\Enums\DatatypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Datatype>
 */
class DatatypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $datatype = DatatypeEnum::random();

        return [
            'name' => $datatype->value,
            'rule' => $datatype->rule(),
            'hint' => $datatype->hint(),
            'developer_hint' => $datatype->developerHint(),
        ];
    }
}

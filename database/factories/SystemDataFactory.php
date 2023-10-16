<?php

namespace Database\Factories;

use App\Enums\SystemDataCode;
use App\Models\Datatype;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemData>
 */
class SystemDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $systemData = SystemDataCode::random();

        return [
            'datatype_id' => Datatype::factory()->state(['name' => $systemData->datatype()]),
            'code' => $systemData->value,
            'title' => $systemData->title(),
            'content' => $systemData->defaultContent(),
            'hint' => $systemData->hint(),
        ];
    }
}

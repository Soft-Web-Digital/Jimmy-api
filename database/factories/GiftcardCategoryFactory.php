<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GiftcardCategory>
 */
class GiftcardCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->company(),
            'icon' => fake()->imageUrl(),
            'sale_term' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the giftcard category is sale_activated.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function saleActivated()
    {
        return $this->state(function (array $attributes) {
            return [
                'sale_activated_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the giftcard category is deleted.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deleted()
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => now(),
            ];
        });
    }
}

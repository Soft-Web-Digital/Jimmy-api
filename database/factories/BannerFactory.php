<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'admin_id' => Admin::factory(),
            'preview_image' => fake()->imageUrl(),
            'featured_image' => fake()->imageUrl(),
        ];
    }

    /**
     * Indicate that the banner is activated.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function activated()
    {
        return $this->state(function (array $attributes) {
            return [
                'activated_at' => now(),
            ];
        });
    }
}

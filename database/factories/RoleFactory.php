<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(),
            'guard_name' => fake()->randomElement(['api_admin', 'api_user']),
        ];
    }

    /**
     * Indicate that the permission has the specified guard.
     *
     * @param string $guard
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function guard(string $guard)
    {
        return $this->state(function (array $attributes) use ($guard) {
            return [
                'guard_name' => $guard,
            ];
        });
    }
}

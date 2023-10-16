<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'group_name' => fake()->dayOfWeek(),
            'guard_name' => fake()->randomElement(['api_user', 'api_user']),
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

<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'country_id' => Country::factory(),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        ];
    }

    /**
     * Indicate that the admin is blocked.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function blocked()
    {
        return $this->state(function (array $attributes) {
            return [
                'blocked_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the admin is verified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the admin is two-fa enabled.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function twoFaEnabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'two_fa_activated_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the admin is deleted.
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

    /**
     * Indicate that the admin is password-secure.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function secure()
    {
        return $this->state(function (array $attributes) {
            return [
                'password_unprotected' => false,
            ];
        });
    }
}

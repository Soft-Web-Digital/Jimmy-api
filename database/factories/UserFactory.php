<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'country_id' => '9a644b5d-ec99-48f2-8c1a-8c9421053ff6',
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'username' => fake()->unique()->userName(),
            'avatar' => fake()->imageUrl(128, 128, 'human'),
        ];
    }

    /**
     * Indicate that the user is blocked.
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
     * Indicate that the user has set transaction PIN.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function enableTransactionPin()
    {
        return $this->state(function (array $attributes) {
            return [
                'transaction_pin' => '$2y$10$VArsqB6fQ65WJcBeWO398elbE0CTPrfFXWV5iqo.V2.Tk6j4D1klS', // 1234
                'transaction_pin_set' => true,
                'transaction_pin_activated_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the user is blocked.
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
     * Indicate that the user is two-fa enabled.
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
     * Indicate that the user is deleted.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deleted()
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => now(),
                'deleted_reason' => fake()->sentence(),
            ];
        });
    }
}

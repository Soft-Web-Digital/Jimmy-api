<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserKyc>
 */
class UserKycFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'user_type' => (new User())->getMorphClass(),
            'bvn' => fake()->iban(),
            'nin' => fake()->iban(),
        ];
    }

    /**
     * Indicate that the kyc bvn is verified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function bvnVerified()
    {
        return $this->state(function (array $attributes) {
            return [
                'bvn_verified_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the kyc nin is verified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function ninVerified()
    {
        return $this->state(function (array $attributes) {
            return [
                'nin_verified_at' => now(),
            ];
        });
    }
}

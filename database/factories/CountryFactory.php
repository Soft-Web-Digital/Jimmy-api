<?php

namespace Database\Factories;

use Faker\Provider\en_US\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->unique()->country(),
            'alpha2_code' => fake()->unique()->countryCode(),
            'alpha3_code' => fake()->unique()->countryISOAlpha3(),
            'dialing_code' => PhoneNumber::areaCode(),
        ];
    }

    /**
     * Indicate that the country is activated for registration.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function registrationActivated()
    {
        return $this->state(function (array $attributes) {
            return [
                'registration_activated_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the country is activated for giftcard.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function giftcardActivated()
    {
        return $this->state(function (array $attributes) {
            return [
                'giftcard_activated_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the country is deleted.
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

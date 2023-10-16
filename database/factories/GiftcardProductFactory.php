<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use App\Models\GiftcardCategory;
use App\Models\GiftcardProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GiftcardProduct>
 */
class GiftcardProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'giftcard_category_id' => GiftcardCategory::factory(),
            'country_id' => Country::query()->first() ?? Country::factory(),
            'currency_id' => Currency::query()->first() ?? Currency::factory(),
            'name' => fake()->company(),
            'sell_rate' => fake()->randomFloat(2, 100, 500),
            'sell_min_amount' => fake()->randomFloat(2, 100, 1000),
            'sell_max_amount' => fake()->randomFloat(2, 1000, 10000),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (GiftcardProduct $giftcardProduct) {
            //
        })->afterCreating(function (GiftcardProduct $giftcardProduct) {
            $giftcardProduct->giftcardCategory->countries()->sync($giftcardProduct->country_id);
        });
    }

    /**
     * Indicate that the giftcard product is activated.
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

    /**
     * Indicate that the giftcard product is deleted.
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

<?php

namespace Database\Factories;

use App\Enums\GiftcardCardType;
use App\Enums\GiftcardTradeType;
use App\Models\Bank;
use App\Models\Giftcard;
use App\Models\GiftcardProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Giftcard>
 */
class GiftcardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'giftcard_product_id' => GiftcardProduct::factory(),
            'bank_id' => Bank::factory(),
            'user_id' => User::factory(),
            'account_name' => fake()->name(),
            'account_number' => fake()->iban(),
            'reference' => fake()->unique()->md5(),
            'trade_type' => GiftcardTradeType::random()->value,
            'card_type' => GiftcardCardType::random()->value,
            'amount' => fake()->randomFloat(2, 100, 1000000),
            'service_charge' => fake()->randomFloat(2, 0, 99),
            'rate' => fake()->randomFloat(2),
            'payable_amount' => fake()->randomFloat(2, 100, 100000),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (Giftcard $giftcard) {
            if ($giftcard->card_type === GiftcardCardType::VIRTUAL) {
                $giftcard->code = fake()->md5();
                $giftcard->pin = fake()->md5();
            }
        })->afterCreating(function (Giftcard $giftcard) {
            //
        });
    }
}

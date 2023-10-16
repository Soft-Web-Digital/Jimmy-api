<?php

namespace Database\Factories;

use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WalletTransaction>
 */
class WalletTransactionFactory extends Factory
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
            'causer_id' => User::factory(),
            'causer_type' => (new User())->getMorphClass(),
            'service' => WalletServiceType::random()->value,
            'type' => WalletTransactionType::random()->value,
            'amount' => fake()->randomFloat(2, 10, 10000),
            'summary' => function (array $attributes) {
                return 'NGN'
                    . number_format($attributes['amount'], 2)
                    . ' was '
                    . WalletTransactionType::from($attributes['type'])->sentenceTerm()
                    . ' your wallet. Triggered by '
                    . User::find($attributes['causer_id'])->full_name;
            },
        ];
    }
}

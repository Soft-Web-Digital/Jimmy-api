<?php

namespace Database\Factories;

use App\Enums\AssetTransactionTradeType;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\Bank;
use App\Models\Network;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetTransaction>
 */
class AssetTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'network_id' => Network::factory(),
            'asset_id' => Asset::factory(),
            'user_id' => User::factory(),
            'reference' => fake()->unique()->md5(),
            'asset_amount' => fake()->randomFloat(18, 1, 999999999999999),
            'rate' => fake()->randomFloat(2),
            'service_charge' => fake()->randomFloat(2, 0, 99),
            'trade_type' =>  AssetTransactionTradeType::random()->value,
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
        return $this->afterMaking(function (AssetTransaction $assetTransaction) {
            if ($assetTransaction->trade_type === AssetTransactionTradeType::SELL) {
                $assetTransaction->bank_id = Bank::factory()->create()->id;
                $assetTransaction->account_name = fake()->iban();
                $assetTransaction->account_number = fake()->iban(length: 10);
            }

            if ($assetTransaction->trade_type === AssetTransactionTradeType::BUY) {
                $assetTransaction->wallet_address = fake()->iban(prefix: '0X', length: 10);
            }
        })->afterCreating(function (AssetTransaction $assetTransaction) {
            //
        });
    }
}

<?php

use App\Enums\AssetTransactionStatus;
use App\Enums\AssetTransactionTradeType;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\Network;
use App\Models\User;
use App\Models\UserBankAccount;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'asset-transaction');





it('can get all asset transactions', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $asset = Asset::factory()->create();
    $network = Network::factory()->create();

    AssetTransaction::factory()->count(2)->create([
        'asset_id' => $asset->id,
        'network_id' => $network->id,
    ]);
    AssetTransaction::factory()->for($user)->count(2)->create([
        'asset_id' => $asset->id,
        'network_id' => $network->id,
    ]);

    getJson('/api/user/asset-transactions')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'asset_transactions' => [
                    'current_page',
                    'data',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ],
            ],
        ])
        ->assertJsonFragment([
            'user_id' => $user->id,
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset transactions fetched successfully')
                    ->has('data.asset_transactions.data', AssetTransaction::where('user_id', $user->id)->count())
        );
});





it('can get user asset transaction stats', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/asset-transactions/stats')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset transaction stats fetched successfully.')
                    ->whereType('data.stats', 'array')
        );
});





it('can get a breakdown for an asset transaction', function ($tradeType) {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $asset = Asset::factory()->create([
        'buy_rate' => 500,
        'sell_rate' => 500,
    ]);

    postJson('/api/user/asset-transactions/breakdown', [
        'asset_id' => $asset->id,
        'trade_type' => $tradeType,
        'asset_amount' => rand(10, 40),
    ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'breakdown' => [
                    'rate',
                    'service_charge',
                    'payable_amount',
                ]
            ]
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset transaction breakdown fetched successfully')
                    ->etc()
        );
})->with(AssetTransactionTradeType::values());





it('can create an asset transaction', function ($tradeType) {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $asset = Asset::factory()->has(Network::factory())->create([
        'buy_rate' => 500,
        'sell_rate' => 500,
    ]);

    $fields = match ($tradeType) {
        AssetTransactionTradeType::BUY->value => [
            'wallet_address' => $wa = fake()->iban(prefix: '0X'),
            'wallet_address_confirmation' => $wa,
        ],
        AssetTransactionTradeType::SELL->value => [
            'user_bank_account_id' => UserBankAccount::factory()->for($user)->create()->id,
        ],
    };

    $data = array_merge([
        'trade_type' => $tradeType,
        'asset_id' => $asset->id,
        'network_id' => $asset->networks()->first()->id,
        'asset_amount' => fake()->randomFloat(18, 5, 10),
    ], $fields);

    postJson('/api/user/asset-transactions', $data)
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset transaction created successfully')
                    ->whereType('data.asset_transaction', 'array')
                    ->where('data.asset_transaction.user_id', $user->id)
                    ->etc()
        );
})->with(AssetTransactionTradeType::values());





it('can get a single asset transaction', function ($id) {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $assetTransaction = AssetTransaction::factory()->for($user)->create();

    getJson("/api/user/asset-transactions/{$assetTransaction->$id}")
        ->assertOk()
        ->assertJsonFragment([
            'user_id' => $user->id,
        ]);
})->with(['id', 'reference']);





it('can transfer an asset transaction', function () {
    test()->seed(PermissionSeeder::class);

    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $assetTransaction = AssetTransaction::factory()->for($user)->create([
        'status' => AssetTransactionStatus::PENDING,
    ]);

    Storage::fake();

    patchJson("/api/user/asset-transactions/{$assetTransaction->id}/transfer", [
        'proof' => fake()->imageUrl(),
//        'proof' => UploadedFile::fake()->image('proof.jpg'),
    ])
        ->assertOk()
        ->assertJsonFragment([
            'status' => AssetTransactionStatus::TRANSFERRED,
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where(
                        'message',
                        'Asset transaction marked as transferred successfully. Admins have been notified.'
                    )
                    ->etc()
        );
});

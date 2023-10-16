<?php

use App\Models\Admin;
use App\Models\AssetTransaction;
use App\Models\Giftcard;
use App\Models\WalletTransaction;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'transaction');





it('can get all system transactions', function () {
    Giftcard::factory()->count(4)->create();
    AssetTransaction::factory()->count(4)->create();
    WalletTransaction::factory()->count(4)->create();

    sanctumLogin(Admin::factory()->secure()->create()->refresh(), ['*'], 'api_admin');

    getJson('/api/admin/transactions')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Transactions fetched successfully')
                    ->has('data.giftcards')
                    ->has('data.asset_transactions')
                    ->has('data.wallet_transactions')
        );
});

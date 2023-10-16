<?php

use App\Models\AssetTransaction;
use App\Models\Giftcard;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'transaction');





it('can get a user\'s transactions', function () {
    $user = User::factory()->create();

    Giftcard::factory()->count(4)->create(['user_id' => $user->id]);
    AssetTransaction::factory()->count(4)->create(['user_id' => $user->id]);

    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/transactions')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Transactions fetched successfully')
                    ->has('data', 2)
                    ->has(
                        'data.stats.0',
                        fn (AssertableJson $json) => $json->whereType('type', 'string')
                            ->whereType('total_transactions_count', 'integer')
                            ->whereType('total_transactions_amount', ['double', 'integer'])
                    )
                    ->has(
                        'data.records.data',
                        8,
                        fn (AssertableJson $json) => $json->hasAll([
                            'id',
                            'type',
                            'reference',
                            'status',
                            'currency',
                            'trade_type',
                            'amount',
                            'payable_amount',
                            'rate',
                            'review_rate',
                            'service_charge',
                            'category_name',
                            'category_icon',
                            'created_at',
                        ])
                    )
        );
});

<?php

use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\WalletTransaction;
use Database\Seeders\PermissionSeeder;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'wallet');





it('fetches wallet transactions', function () {
    $user = User::factory()->create()->refresh();

    WalletTransaction::factory()->for($user, 'user')->count(5)->create();

    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/wallet-transactions')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'wallet_transactions' => [
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
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Wallet transactions fetched successfully.')
                    ->has(
                        'data.wallet_transactions.data',
                        WalletTransaction::whereMorphedTo('user', $user)->paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'bank_id',
                                'account_name',
                                'account_number',
                                'amount',
                                'service',
                                'type',
                                'status',
                                'summary',
                                'admin_note',
                                'receipt',
                                'created_at',
                            ])
                    )
        );
});





it('filters wallet transactions based on value in column', function ($data) {
    ['column' => $column, 'value' => $value] = $data;

    $user = User::factory()->create()->refresh();

    $attributes = match ($column) {
        'service' => ['service' => $value],
        'type' => ['type' => $value],
    };

    WalletTransaction::factory()->for($user, 'user')->count(5)->create($attributes);

    $query = http_build_query([
        "filter[{$column}]" => $value,
    ]);

    sanctumLogin($user, ['*'], 'api_user');

    getJson("/api/user/wallet-transactions?{$query}")
        ->assertOk()
        ->assertJsonCount(5, 'data.wallet_transactions.data');
})->with([
    'service' => fn () => [
        'column' => 'service',
        'value' => WalletServiceType::random()->value,
    ],
    'type' => fn () => [
        'column' => 'type',
        'value' => WalletTransactionType::random()->value,
    ],
]);





it('sorts the notifications by a column in a certain order', function ($order, $column) {
    $symbol = $order === 'asc' ? '' : '-';

    $user = User::factory()->create()->refresh();

    WalletTransaction::factory()->for($user, 'user')->count(5)->create();

    sanctumLogin($user, ['*'], 'api_user');

    $response = getJson("/api/user/wallet-transactions?sort={$symbol}{$column}");

    $response->assertOk();

    $sortedTransactions = collect(
        WalletTransaction::whereMorphedTo('user', $user)->orderBy($column, $order)->paginate()->items()
    )
        ->pluck($column)
        ->map(function ($item) {
            if ($item instanceof \Illuminate\Support\Carbon) {
                return now()->parse($item)->toISOString();
            }

            return $item;
        })
        ->toArray();

    $responseTransactions = $response->collect('data.wallet_transactions.data')->pluck($column)->toArray();

    expect($sortedTransactions === $responseTransactions)->toBeTrue();
})->with([
    'asc',
    'desc',
])->with([
    'created_at',
]);





it('can view a wallet transaction', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $walletTransaction = WalletTransaction::factory()->for($user, 'user')->create()->refresh();

    getJson("/api/user/wallet-transactions/{$walletTransaction->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Wallet transaction fetched successfully.')
                    ->has('data.wallet_transaction', fn ($json) => $json->hasAll([
                        'id',
                        'bank_id',
                        'account_name',
                        'account_number',
                        'amount',
                        'service',
                        'type',
                        'status',
                        'summary',
                        'admin_note',
                        'receipt',
                        'created_at',
                    ]))
        );
});





it('can request a withdrawal', function () {
    test()->seed(PermissionSeeder::class);

    $user = User::factory()->create(['wallet_balance' => 10000])->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $userBankAccount = UserBankAccount::factory()->for($user)->create()->refresh();

    postJson('/api/user/wallet-transactions/withdraw', [
        'user_bank_account_id' => $userBankAccount->id,
        'amount' => 5000,
    ])
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Wallet withdrawal request submitted successfully.')
                    ->whereType('data.wallet_transaction', 'array')
        );
});





it('can close a pending wallet transaction', function () {
    $user = User::factory()->create(['wallet_balance' => 10000])->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $walletTransaction = WalletTransaction::factory()->for($user, 'user')->create([
        'status' => WalletTransactionStatus::PENDING,
    ]);

    patchJson("/api/user/wallet-transactions/{$walletTransaction->id}/close")
        ->assertOk()
        ->assertJsonFragment([
            'status' => WalletTransactionStatus::CLOSED->value,
        ]);
});

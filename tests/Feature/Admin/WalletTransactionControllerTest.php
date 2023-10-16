<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Enums\WalletTransactionStatus;
use App\Models\Admin;
use App\Models\Bank;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;

uses()->group('api', 'wallet-transaction');





it('rejects unpermitted admin from hitting the wallet transaction APIs', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/wallet-transactions{$path}")
        ->assertStatus(Response::HTTP_FORBIDDEN)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::GENERAL_ERROR->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
})->with([
    'index' => fn () => [
        'method' => 'GET',
        'path' => '/',
    ],
    'show' => fn () => [
        'method' => 'GET',
        'path' => '/' . WalletTransaction::factory()->create()->id,
    ],
    'decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . WalletTransaction::factory()->create()->id . '/decline',
    ],
    'approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . WalletTransaction::factory()->create()->id . '/approve',
    ],
]);





it('can get wallet transactions in a paginated format', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_WALLET_TRANSACTIONS);

    WalletTransaction::factory()->create();

    getJson('/api/admin/wallet-transactions')
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
                    ->where('message', 'Wallet transactions fetched successfully')
                    ->has(
                        'data.wallet_transactions.data',
                        WalletTransaction::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(WalletTransaction::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('can view a wallet transaction', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_WALLET_TRANSACTIONS);

    $walletTransaction = WalletTransaction::factory()
        ->for(User::factory()->create(['wallet_balance' => 5000]), 'user')
        ->for(Bank::factory()->create(), 'bank')
        ->create([
            'status' => WalletTransactionStatus::PENDING,
            'amount' => 1000,
        ])
        ->refresh();

    getJson("/api/admin/wallet-transactions/{$walletTransaction->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Wallet transaction fetched successfully')
                    ->has('data.wallet_transaction', fn (AssertableJson $json) => $json->hasAll(array_merge([
                        'bank.id',
                        'bank.name',
                        'user.id',
                        'user.firstname',
                        'user.lastname',
                        'user.email',
                        'user.wallet_balance',
                    ], array_keys($walletTransaction->toArray()))))
        );
});





it('can decline a wallet transaction', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_WALLET_TRANSACTIONS);

    $walletTransaction = WalletTransaction::factory()
        ->create(['status' => WalletTransactionStatus::PENDING])
        ->refresh();

    patchJson("/api/admin/wallet-transactions/{$walletTransaction->id}/decline")
        ->assertOk()
        ->assertJsonFragment([
            'status' => WalletTransactionStatus::DECLINED->value,
        ]);
});





it('can approve a wallet transaction', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_WALLET_TRANSACTIONS);

    $walletTransaction = WalletTransaction::factory()
        ->for(User::factory()->create(['wallet_balance' => 5000]), 'user')
        ->for(Bank::factory()->create(), 'bank')
        ->create([
            'status' => WalletTransactionStatus::PENDING,
            'amount' => 1000,
        ])
        ->refresh();

    patchJson("/api/admin/wallet-transactions/{$walletTransaction->id}/approve")
        ->assertOk()
        ->assertJsonFragment([
            'status' => WalletTransactionStatus::COMPLETED->value,
        ]);
});

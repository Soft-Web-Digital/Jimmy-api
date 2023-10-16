<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'user');





it('can get users in a paginated format', function () {
    $users = User::factory()->count(20)->create();

    $user = $users->first();

    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/users')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'users' => [
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
                    ->where('message', 'Users fetched successfully.')
                    ->has(
                        'data.users.data',
                        User::where('id', '!=', $user->id)->paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'firstname',
                                'lastname',
                                'email',
                            ])
                    )
        );
});





it('can get users in a non-paginated format', function () {
    $users = User::factory()->count(20)->create();

    $user = $users->first();

    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/users?do_not_paginate=1')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Users fetched successfully.')
                    ->has(
                        'data.users',
                        User::where('id', '!=', $user->id)->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'firstname',
                                'lastname',
                                'email',
                            ])
                    )
        );
});





it('filters users by email', function ($data) {
    ['column' => $column, 'value' => $value] = $data;

    $countQuery = match ($column) {
        'email' => User::query()->where('email', 'LIKE', "%{$value}%"),
    };

    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    $query = http_build_query([
        "filter[{$column}]" => $value,
    ]);

    getJson("/api/user/users?{$query}")
        ->assertOk()
        ->assertJsonCount($countQuery->where('id', '!=', $user->id)->count(), 'data.users.data');
})->with([
    'email' => fn () => [
        'column' => 'email',
        'value' => User::factory()->create()->email,
    ],
]);





it('requires an amount for fund transfer', function () {
    $users = User::factory()->count(2)->create();

    $sender = $users->first();
    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = $users->last();

    postJson("/api/user/users/{$receiver->id}/transfer")
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('amount', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.amount.0', trans('validation.required', ['attribute' => 'amount']))
                    ->etc()
        );
});





it('hits a validation error on invalid amount for fund transfer', function () {
    $users = User::factory()->count(2)->create();

    $sender = $users->first();
    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = $users->last();

    postJson("/api/user/users/{$receiver->id}/transfer", [
        'amount' => 'abc',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('amount', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.amount.0', trans('validation.numeric', ['attribute' => 'amount']))
                    ->etc()
        );
});





it('hits a validation error on insufficient amount for fund transfer', function () {
    $users = User::factory()->count(2)->create();

    $sender = $users->first()->refresh();
    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = $users->last();

    postJson("/api/user/users/{$receiver->id}/transfer", [
        'amount' => 5000,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('amount', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where(
                    'data.errors.amount.0',
                    trans('validation.lte.numeric', [
                        'attribute' => 'amount',
                        'value' => (float) $sender->wallet_balance
                    ])
                )->etc()
        );
});





it('hits a validation error on amount less than 1 for fund transfer', function () {
    $users = User::factory()->count(2)->create();

    $sender = $users->first()->refresh();
    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = $users->last();

    postJson("/api/user/users/{$receiver->id}/transfer", [
        'amount' => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('amount', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where(
                    'data.errors.amount.0',
                    trans('validation.min.numeric', [
                        'attribute' => 'amount',
                        'min' => 10
                    ])
                )->etc()
        );
});





it('requires the transaction pin if activated for sender during transfer', function () {
    $sender = User::factory()->create([
        'wallet_balance' => 5000,
        'transaction_pin_set' => true,
        'transaction_pin' => Hash::make((string) 1234),
        'transaction_pin_activated_at' => now(),
    ]);

    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = User::factory()->create();

    postJson("/api/user/users/{$receiver->id}/transfer", [
        'amount' => 5000,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('transaction_pin', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where(
                    'data.errors.transaction_pin.0',
                    trans('validation.required', [
                        'attribute' => 'transaction pin',
                    ])
                )->etc()
        );
});





it('requires a correct transaction pin if activated for sender during transfer', function () {
    $sender = User::factory()->create([
        'wallet_balance' => 5000,
        'transaction_pin_set' => true,
        'transaction_pin' => Hash::make((string) 1234),
        'transaction_pin_activated_at' => now(),
    ]);

    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = User::factory()->create();

    postJson("/api/user/users/{$receiver->id}/transfer", [
        'amount' => 5000,
        'transaction_pin' => 0000,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('transaction_pin', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where(
                    'data.errors.transaction_pin.0',
                    trans('validation.transaction_pin.incorrect', [
                        'attribute' => 'transaction pin',
                    ])
                )->etc()
        );
});





it('can make a fund transfer', function () {
    $users = User::factory()->count(2)->create(['wallet_balance' => 10000]);

    $sender = $users->first()->refresh();
    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = $users->last();

    postJson("/api/user/users/{$receiver->id}/transfer", [
        'amount' => 5000,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Fund transferred successfully.')
                    ->where('data', null)
        );

    expect($sender->refresh()->wallet_balance)->toBe((float) 10000 - 5000);
    expect($receiver->refresh()->wallet_balance)->toBe((float) 10000 + 5000);
});





it('can make a fund transfer with a receipt', function () {
    $users = User::factory()->count(2)->create(['wallet_balance' => 10000]);

    $sender = $users->first()->refresh();
    sanctumLogin($sender, ['*'], 'api_user');

    $receiver = $users->last();

    Storage::fake();

    postJson("/api/user/users/{$receiver->id}/transfer", [
        'amount' => 5000,
        'receipt' => UploadedFile::fake()->image('receipts/receipt.jpg', 350, 350)
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Fund transferred successfully.')
                    ->where('data', null)
        );

    expect($sender->refresh()->walletTransactions()->latest()->first())->receipt->not->toBeNull();
    expect($receiver->refresh()->walletTransactions()->latest()->first())->receipt->not->toBeNull();
});

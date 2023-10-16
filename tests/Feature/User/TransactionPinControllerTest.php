<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'user');





it('requires the old pin for transaction pin update', function () {
    sanctumLogin(User::factory()->create(['transaction_pin_set' => true]), ['*'], 'api_user');

    patchJson('/api/user/transaction-pin')
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('old_pin', 'data.errors');
});





it('verifies the old pin is correct for an existing user', function ($oldPin) {
    $message = match ($oldPin) {
        101 => trans('validation.digits', ['attribute' => 'old pin', 'digits' => 4]),
        11011 => trans('validation.digits', ['attribute' => 'old pin', 'digits' => 4]),
        1101 => trans('validation.transaction_pin.incorrect', ['attribute' => 'old pin']),
    };

    $attributes = ['transaction_pin_set' => true];

    sanctumLogin(User::factory()->create($attributes), ['*'], 'api_user');

    patchJson('/api/user/transaction-pin', [
        'old_pin' => $oldPin,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('old_pin', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.old_pin.0', $message)
                    ->etc()
        );
})->with([
    'less than 4 digits' => 101,
    'more than 4 digits' => 11011,
    'incorrect' => 1101,
]);





it('verifies the new pin for transaction pin update', function ($newPin) {
    $message = match ($newPin) {
        101 => trans('validation.digits', ['attribute' => 'new pin', 'digits' => 4]),
        11011 => trans('validation.digits', ['attribute' => 'new pin', 'digits' => 4]),
        1234 => trans('validation.transaction_pin.uncompromised', ['attribute' => 'new pin']),
        default => trans('validation.confirmed', ['attribute' => 'new pin']),
    };

    sanctumLogin(User::factory()->create(), ['*'], 'api_user');

    patchJson('/api/user/transaction-pin', [
        'new_pin' => $newPin,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('new_pin', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.new_pin.0', $message)
                    ->etc()
        );
})->with([
    'less than 4 digits' => 101,
    'more than 4 digits' => 11011,
    'compromised' => 1234,
    'no confirmation' => 1201,
]);





it('can update transaction pin for an existing user', function () {
    $oldPin = 2323;

    $user = User::factory()->create([
        'transaction_pin_set' => true,
        'transaction_pin' => Hash::make((string) $oldPin),
    ]);

    sanctumLogin($user, ['*'], 'api_user');

    patchJson('/api/user/transaction-pin', [
        'old_pin' => $oldPin,
        'new_pin' => 1212,
        'new_pin_confirmation' => 1212,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Transaction PIN updated successfully.')
                    ->where('data', null)
        );
});





it('can request a transaction pin reset', function () {
    sanctumLogin(User::factory()->create(), ['*'], 'api_user');

    postJson('/api/user/transaction-pin/forgot')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Transaction PIN reset code sent successfully.')
                    ->where('data', null)
        );
});





it('verifies the pin for transaction pin reset', function ($pin) {
    $message = match ($pin) {
        101 => trans('validation.digits', ['attribute' => 'pin', 'digits' => 4]),
        11011 => trans('validation.digits', ['attribute' => 'pin', 'digits' => 4]),
        1234 => trans('validation.transaction_pin.uncompromised', ['attribute' => 'pin']),
        default => trans('validation.confirmed', ['attribute' => 'pin']),
    };

    sanctumLogin(User::factory()->create(), ['*'], 'api_user');

    postJson('/api/user/transaction-pin/reset', [
        'pin' => $pin,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('pin', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.pin.0', $message)
                    ->etc()
        );
})->with([
    'less than 4 digits' => 101,
    'more than 4 digits' => 11011,
    'compromised' => 1234,
    'no confirmation' => 1201,
]);





it('can reset transaction pin', function () {
    $user = User::factory()->create();

    $code = $user->generateTransactionPinResetCodeModel()->getCode();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/transaction-pin/reset', [
        'code' => $code,
        'pin' => 1212,
        'pin_confirmation' => 1212,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Transaction PIN reset successfully.')
                    ->where('data', null)
        );
});





it('can toggle transaction pin activation', function () {
    $user = User::factory()->create(['transaction_pin_set' => true]);

    sanctumLogin($user, ['*'], 'api_user');

    patchJson('/api/user/transaction-pin/activation')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Transaction PIN activation toggled successfully.')
                    ->whereType('data.status', 'boolean')
        );
});

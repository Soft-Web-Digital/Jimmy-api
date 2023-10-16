<?php

use App\Exceptions\NotAllowedException;
use App\Models\TransactionPinResetCode;
use App\Models\User;
use App\Notifications\Auth\TransactionPinResetNotification;
use App\Notifications\Auth\TransactionPinUpdatedNotification;
use App\Services\Auth\TransactionPinService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses()->group('service', 'auth');





it('can update user transaction pin', function () {
    $user = User::factory()->create();

    Notification::fake();

    $pin = (string) mt_rand(0000, 9999);
    (new TransactionPinService())->update($user, $pin);

    $user->refresh();

    Notification::assertSentTo($user, TransactionPinUpdatedNotification::class);

    expect($user->transaction_pin_set)->toBeTrue();

    expect(Hash::check($pin, $user->transaction_pin))->toBeTrue();
});





it('can request reset code', function () {
    $user = User::factory()->create();

    Notification::fake();

    (new TransactionPinService())->requestResetCode($user);

    Notification::assertSentTo($user, TransactionPinResetNotification::class);
});





it('throws a ValidationException if reset code is invalid or expired', function ($status) {
    $user = User::factory()->create();

    $code = $status === 'invalid'
        ? (string) mt_rand(0000, 9999)
        : $user->generateTransactionPinResetCodeModel()->getCode();

    test()->travel(TransactionPinResetCode::EXPIRATION_TIME_IN_MINUTES + 1)->minutes();

    expect(fn () => (new TransactionPinService())->reset($user, $code, (string) mt_rand(0000, 9999)))
        ->toThrow(ValidationException::class, trans("auth.code.{$status}"));
})->with([
    'invalid',
    'expired',
]);





it('can toggle transaction pin activation', function ($status) {
    $attributes = match ($status) {
        true => ['transaction_pin_set' => true, 'transaction_pin_activated_at' => now()],
        default => ['transaction_pin_set' => true, 'transaction_pin_activated_at' => null],
    };

    $user = User::factory()->create($attributes);

    (new TransactionPinService())->toggleActivation($user);

    expect((bool) $user->transaction_pin_activated_at)->toBe(!$status ? true : false);
})->with([
    'ON' => fn () => true,
    'OFF' => fn () => false,
]);





it('throws a NotAllowedException if user has not set transaction pin on toggle', function () {
    (new TransactionPinService())->toggleActivation(User::factory()->create());
})->throws(NotAllowedException::class, 'You need to set a transaction PIN');

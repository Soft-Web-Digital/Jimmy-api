<?php

use App\Exceptions\ExpectationFailedException;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Services\Auth\ResetPasswordService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses()->group('service', 'auth');





it('throws an InvalidArgumentException when broker is not defined on request', function ($fakeBroker) {
    expect(fn () => (new ResetPasswordService())->request(fake()->email(), $fakeBroker))
        ->toThrow(\InvalidArgumentException::class, "Password resetter [{$fakeBroker}] is not defined.");
})->with([
    'niklaus',
    'elijah',
    'rebecca',
    'kol',
    'finn',
]);





it('can request a reset password', function ($user) {
    $config = config("auth.passwords.{$user->getMorphClass()}s");

    Notification::fake();

    (new ResetPasswordService())->request($user->email, $config['provider']);

    Notification::assertSentTo($user, ResetPasswordNotification::class);

    test()->assertDatabaseHas($config['table'], [
        'email' => $user->email,
    ]);
})->with('authenticable_models');





it('throws an ExpectationFailedException on failed request', function ($broker) {
    (new ResetPasswordService())->request(fake()->email(), $broker);
})->with([
    'users',
    'admins',
])->throws(ExpectationFailedException::class);





it('returns a matched string after request', function ($user) {
    $config = config("auth.passwords.{$user->getMorphClass()}s");

    $status = (new ResetPasswordService())->request($user->email, $config['provider']);

    expect($status)
        ->toBeString()
        ->toBe(trans(Password::RESET_LINK_SENT));
})->with('authenticable_models');





it('throws an ExpectationFailedException if reset code/token is invalid', function ($user) {
    $broker = config("auth.passwords.{$user->getMorphClass()}s.provider");

    expect(fn () => (new ResetPasswordService())->verify($user->email, (string) mt_rand(000000, 999999), $broker))
        ->toThrow(ExpectationFailedException::class, trans('passwords.token'));
})->with('authenticable_models');





it('throws a ExpectationFailedException if reset code/token is expired', function ($user) {
    $config = config("auth.passwords.{$user->getMorphClass()}s");

    $code = mt_rand(000000, 999999);

    DB::table($config['table'])->insert([
        'email' => $user->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    test()->travelTo(now()->addMinutes((int) $config['expire'] + 1));

    expect(fn () => (new ResetPasswordService())->verify($user->email, $code, $config['provider']))
        ->toThrow(ExpectationFailedException::class, trans('passwords.token'));
})->with('authenticable_models');





it('can verify the reset code/token', function ($user) {
    $config = config("auth.passwords.{$user->getMorphClass()}s");

    $code = mt_rand(000000, 999999);

    DB::table($config['table'])->insert([
        'email' => $user->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    expect((new ResetPasswordService())->verify($user->email, $code, $config['provider']))
        ->toBeString()
        ->toBe(trans('passwords.valid_token'));
})->with('authenticable_models');





it('can reset password', function ($user) {
    $config = config("auth.passwords.{$user->getMorphClass()}s");

    $code = mt_rand(000000, 999999);

    DB::table($config['table'])->insert([
        'email' => $user->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    Event::fake();

    $status = (new ResetPasswordService())->reset($user->email, 'password', $code, $config['provider']);

    Event::assertDispatched(PasswordReset::class);

    expect($status)->toBeString();
})->with('authenticable_models');





it('deletes the reset code/token after reset', function ($user) {
    $config = config("auth.passwords.{$user->getMorphClass()}s");

    $code = mt_rand(000000, 999999);

    DB::table($config['table'])->insert([
        'email' => $user->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    (new ResetPasswordService())->reset($user->email, 'password', $code, $config['provider']);

    test()->assertDatabaseMissing($config['table'], [
        'email' => $user->email,
    ]);
})->with('authenticable_models');





it('throws an ExpectationFailedException during reset', function ($user) {
    $config = config("auth.passwords.{$user->getMorphClass()}s");

    $code = mt_rand(000000, 999999);

    (new ResetPasswordService())->reset($user->email, 'password', $code, $config['provider']);
})->with('authenticable_models')->throws(ExpectationFailedException::class);

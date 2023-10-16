<?php

use App\DataTransferObjects\Auth\AuthenticationCredentials;
use App\Models\Admin;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Notifications\Auth\TwofaNotification;
use App\Services\Auth\LoginService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses()->group('service', 'auth');





it('throws a ValidationException when login record is not found', function ($user) {
    expect(fn () => (new LoginService())->start('email', $user->email, 'PassWord', $user))
        ->toThrow(ValidationException::class, trans('auth.failed'));
})->with('authenticable_models');






it('sends a TwoFaNotification if user has two-fa activated', function ($user) {
    Notification::fake();

    (new LoginService())->start('email', $user->email, 'password', $user);

    Notification::assertSentTo($user, TwofaNotification::class);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('does not send a TwoFaNotification if user has 2FA deactivated', function ($user) {
    Notification::fake();

    (new LoginService())->start('email', $user->email, 'password', $user);

    Notification::assertNothingSent();
})->with('authenticable_models');





it('generates a user token that has the ["*"] ability for non two-fa users', function ($user) {
    (new LoginService())->start('email', $user->email, 'password', $user);

    expect($user->tokens()->first())
        ->abilities
            ->toBe(['*'])
            ->not->toBe(['two_fa']);
})->with('authenticable_models');





it('generates a user token that has the ["two_fa"] ability for two-fa users', function ($user) {
    (new LoginService())->start('email', $user->email, 'password', $user);

    expect($user->tokens()->first())
        ->abilities
            ->toBe(['two_fa'])
            ->not->toBe(['*']);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);




it('affirms that the token in the AuthenticationCredentials matches the generated token', function ($user) {
    $authenticationCredentials = (new LoginService())->start('email', $user->email, 'password', $user);

    expect($authenticationCredentials->getToken())->toStartWith(PersonalAccessToken::query()->latest()->first()->id);
})->with('authenticable_models');





it('returns an AuthenticationCredentials DTO on login', function ($user) {
    expect((new LoginService())->start('email', $user->email, 'password', $user))
        ->toBeInstanceOf(AuthenticationCredentials::class);
})->with('authenticable_models');





it('requires two-fa in the AuthenticationCredentials object for two-fa users', function ($user) {
    $authenticationCredentials = (new LoginService())->start('email', $user->email, 'password', $user);

    expect($authenticationCredentials->getTwoFaRequired())->toBeTrue();
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('does not require two-fa in the AuthenticationCredentials object for non two-fa users', function ($user) {
    $authenticationCredentials = (new LoginService())->start('email', $user->email, 'password', $user);

    expect($authenticationCredentials->getTwoFaRequired())->toBeFalse();
})->with('authenticable_models');





it('sets the api message in the AuthenticationCredentials to auth.temp_success for two-fa users', function ($user) {
    $authenticationCredentials = (new LoginService())->start('email', $user->email, 'password', $user);

    expect($authenticationCredentials->getApiMessage())->toBe(trans('auth.temp_success'));
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('sets the api message in the AuthenticationCredentials to auth.success for non two-fa users', function ($user) {
    $authenticationCredentials = (new LoginService())->start('email', $user->email, 'password', $user);

    expect($authenticationCredentials->getApiMessage())->toBe(trans('auth.success'));
})->with('authenticable_models');





it('can invalidate the current access token', function ($user) {
    (new LoginService())->start('email', $user->email, 'password', $user);

    $accessToken = PersonalAccessToken::query()->latest()->first();

    (new LoginService())->stop($user->withAccessToken($accessToken));

    expect($user->tokens->isEmpty())->toBeTrue();
})->with('authenticable_models');





it('can invalidate other access tokens', function ($user, $numberOfTokens) {
    while ($numberOfTokens > 0) {
        (new LoginService())->start('email', $user->email, 'password', $user);
        $numberOfTokens--;
    }

    $accessToken = PersonalAccessToken::query()->latest()->first();

    (new LoginService())->stopOthers($user->withAccessToken($accessToken));

    expect($user->tokens->count())->toBe(1);
})->with('authenticable_models')->with([
    '2 access tokens' => 2,
    '3 access tokens' => 3,
    '4 access tokens' => 4,
]);

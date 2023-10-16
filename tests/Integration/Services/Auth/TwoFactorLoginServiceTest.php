<?php

use App\DataTransferObjects\Auth\AuthenticationCredentials;
use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\PersonalAccessToken;
use App\Models\TwoFaVerificationCode;
use App\Models\User;
use App\Notifications\Auth\TwofaNotification;
use App\Services\Auth\TwoFactorLoginService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses()->group('service', 'auth');





it('throws a ValidationException when two-fa verification code is invalid', function ($user) {
    expect(fn () => (new TwoFactorLoginService())->verify($user, mt_rand(000000, 999999)))
        ->toThrow(ValidationException::class, trans('auth.code.invalid'));
})->with('authenticable_models');





it('throws a ValidationException when two-fa verification code is expired', function ($user) {
    $code = $user->generateTwoFaVerificationCodeModel()->getCode();

    test()->travelTo(now()->addMinutes(TwoFaVerificationCode::EXPIRATION_TIME_IN_MINUTES + 1));

    expect(fn () => (new TwoFactorLoginService())->verify($user, $code))
        ->toThrow(ValidationException::class, trans('auth.code.expired'));
})->with('authenticable_models');





it('throws a NotAllowedException for already authenticated users', function ($user) {
    $user = userWithAccessToken($user);

    (new TwoFactorLoginService())->verify($user, mt_rand(000000, 999999));
})->with('authenticable_models')->throws(NotAllowedException::class, 'You have completed your authentication already');





it('complete two-fa login by returning an AuthenticationCredentials DTO on verification', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    expect((new TwoFactorLoginService())->verify($user, $user->generateTwoFaVerificationCodeModel()->getCode()))
        ->toBeInstanceOf(AuthenticationCredentials::class);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('deletes the email verification code on verification', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    $twoFaVerificationModelData = $user->generateTwoFaVerificationCodeModel();

    (new TwoFactorLoginService())->verify($user, $twoFaVerificationModelData->getCode());

    test()->assertDatabaseMissing((new TwoFaVerificationCode())->getTable(), [
        'user_id' => $user->id,
        'user_type' => $user->getMorphClass(),
    ]);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('invalidates the existing access token', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    (new TwoFactorLoginService())->verify($user, $user->generateTwoFaVerificationCodeModel()->getCode());

    test()->assertModelMissing($user->currentAccessToken());
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('sets the api message in the AuthenticationCredentials to auth.success', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    $authenticationCredentials = (new TwoFactorLoginService())->verify(
        $user,
        $user->generateTwoFaVerificationCodeModel()->getCode()
    );

    expect($authenticationCredentials->getApiMessage())->toBe(trans('auth.success'));
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('creates an access token for the user', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    $authenticationCredentials = (new TwoFactorLoginService())->verify(
        $user,
        $user->generateTwoFaVerificationCodeModel()->getCode()
    );

    expect($authenticationCredentials->getToken())
        ->not->toBeEmpty();
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('creates an access token with the ["*"] ability', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    (new TwoFactorLoginService())->verify($user, $user->generateTwoFaVerificationCodeModel()->getCode());

    expect($user->tokens()->first())
        ->abilities
            ->toBe(['*'])
            ->not->toBe(['two_fa']);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('affirms that the token in the AuthenticationCredentials matches the generated token', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    $authCred = (new TwoFactorLoginService())->verify($user, $user->generateTwoFaVerificationCodeModel()->getCode());

    expect($authCred->getToken())
        ->toStartWith(PersonalAccessToken::query()->latest()->first()->id);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('throws a NotAllowedException for already authenticated users during resend', function ($user) {
    $user = userWithAccessToken($user);

    (new TwoFactorLoginService())->resend($user);
})->with('authenticable_models')->throws(NotAllowedException::class, 'You have completed your authentication already');





it('sends a TwoFaNotification to the user on resend', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    Notification::fake();

    (new TwoFactorLoginService())->resend($user);

    Notification::assertSentTo($user, TwofaNotification::class);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('generates a two-fa code on resend', function ($user) {
    $user = userWithAccessToken($user, ['two_fa']);

    (new TwoFactorLoginService())->resend($user);

    test()->assertDatabaseHas((new TwoFaVerificationCode())->getTable(), [
        'user_id' => $user->id,
        'user_type' => $user->getMorphClass(),
    ]);
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);

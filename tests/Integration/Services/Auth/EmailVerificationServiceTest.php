<?php

use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses()->group('service', 'auth');





it('throws a NotAllowedException if user has verified their email', function ($user) {
    (new EmailVerificationService())->verify($user, mt_rand(000000, 999999));
})->with([
    'user' => fn () => User::factory()->verified()->create(),
    'admin' => fn () => Admin::factory()->verified()->create(),
])->throws(NotAllowedException::class, 'User email already verified');





it('throws a ValidationException if user does not have any email verification codes', function ($user) {
    expect(fn () => (new EmailVerificationService())->verify($user, mt_rand(10000, 999999)))
        ->toThrow(ValidationException::class, trans('auth.code.invalid'));
})->with('authenticable_models');





it('throws a ValidationException if user has an expired email verification code', function ($user) {
    $code = $user->generateEmailVerificationCodeModel()->getCode();

    test()->travelTo(now()->addMinutes((int) config('auth.verification.expire', 60) + 1));

    expect(fn () => (new EmailVerificationService())->verify($user, $code))
        ->toThrow(ValidationException::class, trans('auth.code.expired'));
})->with('authenticable_models');





it('verifies user email', function ($user) {
    $code = $user->generateEmailVerificationCodeModel()->getCode();

    (new EmailVerificationService())->verify($user, $code);

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull();
})->with('authenticable_models');





it('deletes the email verification code used after verifying', function ($user) {
    $code = $user->generateEmailVerificationCodeModel()->getCode();

    (new EmailVerificationService())->verify($user, $code);

    test()->assertDatabaseMissing((new EmailVerificationCode())->getTable(), [
        'user_id' => $user->id,
        'user_type' => $user->getMorphClass(),
    ]);
})->with('authenticable_models');





it('fires a Verified event after verifying', function ($user) {
    $code = $user->generateEmailVerificationCodeModel()->getCode();

    Event::fake();

    (new EmailVerificationService())->verify($user, $code);

    Event::assertDispatched(Verified::class);
})->with('authenticable_models');





it('throws a NotAllowedException if user has been verified while resending', function ($user) {
    (new EmailVerificationService())->resend($user);
})->with([
    'user' => fn () => User::factory()->verified()->create(),
    'admin' => fn () => Admin::factory()->verified()->create(),
])->throws(NotAllowedException::class, 'User email already verified');





it('generates an email verification code for user while resending', function ($user) {
    (new EmailVerificationService())->resend($user);

    test()->assertDatabaseHas((new EmailVerificationCode())->getTable(), [
        'user_id' => $user->id,
        'user_type' => $user->getMorphClass(),
    ]);
})->with('authenticable_models');





it('resends a VerifyEmailNotification via mail to user', function ($user) {
    Notification::fake();

    (new EmailVerificationService())->resend($user);

    Notification::assertSentTo($user, VerifyEmailNotification::class);
})->with('authenticable_models');





it('resends a VerifyEmailNotification via database to user', function ($user) {
    (new EmailVerificationService())->resend($user);

    expect(DatabaseNotification::query()->first())
        ->type->toBe(VerifyEmailNotification::class);
})->with('authenticable_models');

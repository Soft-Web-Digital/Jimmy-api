<?php

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'user');





it('requires the code to verify email verification', function () {
    sanctumLogin(User::factory()->create(), ['*'], 'api_user');

    postJson('/api/user/email/verify')
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('code', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.required', [
                        'attribute' => 'code',
                    ]))
                    ->etc()
        );
});





it('hits a validation code if code is not a string to verify email verification', function () {
    sanctumLogin(User::factory()->create(), ['*'], 'api_user');

    postJson('/api/user/email/verify', [
        'code' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('code', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.string', [
                        'attribute' => 'code',
                    ]))
                    ->etc()
        );
});





it('can verify email verification', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    $code = $user->generateEmailVerificationCodeModel()->getCode();

    postJson('/api/user/email/verify', [
        'code' => $code,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Email verified successfully')
                    ->whereType('data', 'null')
        );
});





it('can resend email verification code', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/email/resend')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Email verification code resent successfully')
                    ->whereType('data', 'null')
        );
});





it('throws a Too-Many-Requests exception when resending email verification code in under 1 minute', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/email/resend')->assertOk();

    postJson('/api/user/email/resend')
        ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::GENERAL_ERROR->value)
                    ->where('locale', 'en')
                    ->where('message', 'Too Many Attempts.')
                    ->whereType('data', 'null')
        );
});





it('does not throttle for 1 minute for resending email verification code', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/email/resend')->assertOk();

    test()->travelTo(now()->addSeconds(61));

    postJson('/api/user/email/resend')->assertOk();
});

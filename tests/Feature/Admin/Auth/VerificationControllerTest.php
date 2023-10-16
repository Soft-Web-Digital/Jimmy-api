<?php

use App\Enums\ApiErrorCode;
use App\Models\Admin;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'admin');





it('requires the code to verify email verification', function () {
    sanctumLogin(Admin::factory()->create(), ['*'], 'api_admin');

    postJson('/api/admin/email/verify')
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
    sanctumLogin(Admin::factory()->create(), ['*'], 'api_admin');

    postJson('/api/admin/email/verify', [
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
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    $code = $admin->generateEmailVerificationCodeModel()->getCode();

    postJson('/api/admin/email/verify', [
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
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/email/resend')
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
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/email/resend')->assertOk();

    postJson('/api/admin/email/resend')
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
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/email/resend')->assertOk();

    test()->travelTo(now()->addSeconds(61));

    postJson('/api/admin/email/resend')->assertOk();
});

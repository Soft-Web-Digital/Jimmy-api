<?php

use App\Enums\ApiErrorCode;
use App\Models\Admin;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'admin');





it('cannot allow access to full-auth admin to verify two-fa login', function () {
    sanctumLogin(Admin::factory()->create(), ['*']);

    postJson('/api/admin/verify-two-fa')
        ->assertStatus(Response::HTTP_NOT_ACCEPTABLE)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::NOT_ALLOWED->value)
                    ->where('locale', 'en')
                    ->where('message', 'You have completed your authentication already')
                    ->whereType('data', 'null')
        );
});





it('cannot allow access to unauthenticated admin to verify two-fa login', function () {
    postJson('/api/admin/verify-two-fa')
        ->assertUnauthorized()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::GENERAL_ERROR->value)
                    ->where('locale', 'en')
                    ->where('message', 'Unauthenticated.')
                    ->whereType('data', 'null')
        );
});





it('requires the code to verify two-fa login', function () {
    sanctumLogin(Admin::factory()->twoFaEnabled()->create(), ['two_fa'], 'api_admin');

    postJson('/api/admin/verify-two-fa')
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





it('hits a validation code if code is not a string to verify two-fa login', function () {
    sanctumLogin(Admin::factory()->twoFaEnabled()->create(), ['two_fa'], 'api_admin');

    postJson('/api/admin/verify-two-fa', [
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





it('can verify two-fa login', function () {
    $admin = Admin::factory()->twoFaEnabled()->create();

    sanctumLogin($admin, ['two_fa'], 'api_admin');

    $code = $admin->generateTwoFaVerificationCodeModel()->getCode();

    postJson('/api/admin/verify-two-fa', [
        'code' => $code,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->has(
                        'data',
                        fn (AssertableJson $json) =>
                            $json->whereType('token', 'string')
                                ->where('admin', $admin->toArray())
                    )
        );
});





it('cannot allow access to full-auth admin to resend two-fa code', function () {
    sanctumLogin(Admin::factory()->create(), ['*'], 'api_admin');

    postJson('/api/admin/resend-two-fa')
        ->assertStatus(Response::HTTP_NOT_ACCEPTABLE)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::NOT_ALLOWED->value)
                    ->where('locale', 'en')
                    ->where('message', 'You have completed your authentication already')
                    ->whereType('data', 'null')
        );
});





it('cannot allow access to unauthenticated admin to resend two-fa code', function () {
    postJson('/api/admin/resend-two-fa')
        ->assertUnauthorized()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::GENERAL_ERROR->value)
                    ->where('locale', 'en')
                    ->where('message', 'Unauthenticated.')
                    ->whereType('data', 'null')
        );
});





it('can resend two-fa code', function () {
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['two_fa'], 'api_admin');

    postJson('/api/admin/resend-two-fa')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Two-Fa Code Resent Successfully')
                    ->whereType('data', 'null')
        );
});





it('throws a Too-Many-Requests exception when attempting to resend two-fa code in under 1 minute', function () {
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['two_fa'], 'api_admin');

    postJson('/api/admin/resend-two-fa')->assertOk();

    postJson('/api/admin/resend-two-fa')
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

    sanctumLogin($admin, ['two_fa'], 'api_admin');

    postJson('/api/admin/resend-two-fa')->assertOk();

    test()->travelTo(now()->addSeconds(61));

    postJson('/api/admin/resend-two-fa')->assertOk();
});

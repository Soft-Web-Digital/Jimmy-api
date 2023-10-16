<?php

use App\Enums\ApiErrorCode;
use App\Models\Admin;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'admin');





it('can request for a reset password code', function () {
    $admin = Admin::factory()->create();

    postJson('/api/admin/password/forgot', [
        'email' => $admin->email,
    ])
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'code',
            'locale',
            'message',
            'data',
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
});





it('requires the admin email for resetting password', function () {
    postJson('/api/admin/password/forgot', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.required', [
                        'attribute' => 'email',
                    ]))
                    ->etc()
        );
});





it('hits a validation error to validate the email for resetting password', function () {
    postJson('/api/admin/password/forgot', [
        'email' => fake()->word(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.email', [
                        'attribute' => 'email',
                    ]))
                    ->etc()
        );
});





it('hits a validation error to find an existing admin email for resetting password', function () {
    postJson('/api/admin/password/forgot', [
        'email' => fake()->email(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('auth.failed'))
                    ->etc()
        );
});





it('hits a validation error to find an existing, but deleted admin email for resetting password', function () {
    $admin = Admin::factory()->create(['deleted_at' => now()])->refresh();

    postJson('/api/admin/password/forgot', [
        'email' => $admin->email,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('auth.failed'))
                    ->etc()
        );
});





it('can verify a password reset code', function () {
    $admin = Admin::factory()->create();

    $code = (string) mt_rand(000000, 999999);

    $config = config("auth.passwords.{$admin->getMorphClass()}s");

    DB::table($config['table'])->insert([
        'email' => $admin->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    postJson('/api/admin/password/verify', [
        'email' => $admin->email,
        'code' => $code,
    ])
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'code',
            'locale',
            'message',
            'data',
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
});





it('requires the email to verify a password reset code', function () {
    postJson('/api/admin/password/verify', [
        'code' => fake()->word(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.required', [
                        'attribute' => 'email',
                    ]))
                    ->etc()
        );
});





it('requires the code to verify a password reset code', function () {
    $admin = Admin::factory()->create();

    postJson('/api/admin/password/verify', [
        'email' => $admin->email,
    ])
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





it('hits a validation error if code is not a string to verify a password reset code', function () {
    $admin = Admin::factory()->create();

    postJson('/api/admin/password/verify', [
        'email' => $admin->email,
        'code' => [fake()->word(), fake()->word()]
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





it('hits a validation error on invalid email to verify a password reset code', function () {
    postJson('/api/admin/password/verify', [
        'email' => fake()->word(),
        'code' => fake()->word(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.email', [
                        'attribute' => 'email',
                    ]))
                    ->etc()
        );
});





it('hits a validation error if admin email does not exist to verify a password reset code', function () {
    postJson('/api/admin/password/verify', [
        'email' => fake()->email(),
        'code' => fake()->word(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('auth.failed'))
                    ->etc()
        );
});





it('hits a validation error if admin email exists, but deleted to verify a password reset code', function () {
    $admin = Admin::factory()->create(['deleted_at' => now()])->refresh();

    postJson('/api/admin/password/verify', [
        'email' => $admin->email,
        'code' => fake()->word(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('auth.failed'))
                    ->etc()
        );
});





it('fails on invalid code to verify a password reset code', function () {
    $admin = Admin::factory()->create()->refresh();

    postJson('/api/admin/password/verify', [
        'email' => $admin->email,
        'code' => fake()->word(),
    ])
        ->assertStatus(Response::HTTP_EXPECTATION_FAILED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
});





it('fails on expired code to verify a password reset code', function () {
    $admin = Admin::factory()->create()->refresh();

    $code = (string) mt_rand(000000, 999999);

    $config = config("auth.passwords.{$admin->getMorphClass()}s");

    DB::table($config['table'])->insert([
        'email' => $admin->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    test()->travelTo(now()->addMinutes((int) config("auth.passwords.{$admin->getMorphClass()}s.expire")));

    postJson('/api/admin/password/verify', [
        'email' => $admin->email,
        'code' => $code,
    ])
        ->assertStatus(Response::HTTP_EXPECTATION_FAILED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
});





it('requires the email to reset admin password', function () {
    $password = Str::random(8);

    postJson('/api/admin/password/reset', [
        'code' => fake()->word(),
        'password' => $password,
        'password_confirmation' => $password,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.required', [
                        'attribute' => 'email',
                    ]))
                    ->etc()
        );
});





it('requires the code to reset admin password', function () {
    $password = Str::random(8);
    $admin = Admin::factory()->create();

    postJson('/api/admin/password/reset', [
        'email' => $admin->email,
        'password' => $password,
        'password_confirmation' => $password,
    ])
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





it('requires the password to reset admin password', function () {
    $admin = Admin::factory()->create();

    postJson('/api/admin/password/reset', [
        'email' => $admin->email,
        'code' => fake()->word(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('password', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.required', [
                        'attribute' => 'password',
                    ]))
                    ->etc()
        );
});





it('requires the password_confirmation to reset admin password', function () {
    $admin = Admin::factory()->create();
    $password = Str::random(8);

    postJson('/api/admin/password/reset', [
        'email' => $admin->email,
        'code' => fake()->word(),
        'password' => $password,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('password', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.confirmed', [
                        'attribute' => 'password',
                    ]))
                    ->etc()
        );
});





it('requires the password_confirmation to match the password to reset admin password', function () {
    $admin = Admin::factory()->create();
    $password = Str::random(8);

    postJson('/api/admin/password/reset', [
        'email' => $admin->email,
        'code' => fake()->word(),
        'password' => $password,
        'password_confirmation' => $password . 'a',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('password', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.confirmed', [
                        'attribute' => 'password',
                    ]))
                    ->etc()
        );
});





it('hits a validation error on invalid email to reset admin password', function () {
    $password = Str::random(8);

    postJson('/api/admin/password/reset', [
        'email' => fake()->word(),
        'code' => fake()->word(),
        'password' => $password,
        'password_confirmation' => $password,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('validation.email', [
                        'attribute' => 'email',
                    ]))
                    ->etc()
        );
});





it('hits a validation error on non-existent email to reset admin password', function () {
    $password = Str::random(8);

    postJson('/api/admin/password/reset', [
        'email' => fake()->email(),
        'code' => fake()->word(),
        'password' => $password,
        'password_confirmation' => $password,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('auth.failed'))
                    ->etc()
        );
});





it('hits a validation error on existent email, but deleted admin to reset admin password', function () {
    $password = Str::random(8);
    $admin = Admin::factory()->create(['deleted_at' => now()]);

    postJson('/api/admin/password/reset', [
        'email' => $admin->email,
        'code' => fake()->word(),
        'password' => $password,
        'password_confirmation' => $password,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::EXPECTATION_FAILED->value)
                    ->where('locale', 'en')
                    ->where('message', trans('auth.failed'))
                    ->etc()
        );
});





it('hits a validation error on invalid code as a string to reset admin password', function () {
    $password = Str::random(8);
    $admin = Admin::factory()->create();

    postJson('/api/admin/password/reset', [
        'email' => $admin->email,
        'code' => [fake()->word(), fake()->word()],
        'password' => $password,
        'password_confirmation' => $password,
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





it('can reset admin password', function () {
    $password = Str::random(8);
    $admin = Admin::factory()->create();
    $code = (string) mt_rand(000000, 999999);

    $config = config("auth.passwords.{$admin->getMorphClass()}s");

    DB::table($config['table'])->insert([
        'email' => $admin->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    postJson('/api/admin/password/reset', [
        'email' => $admin->email,
        'code' => $code,
        'password' => $password,
        'password_confirmation' => $password,
    ])
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'code',
            'locale',
            'message',
            'data',
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
});

<?php

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'user');





it('can request for a reset password code', function () {
    $user = User::factory()->create();

    postJson('/api/user/password/forgot', [
        'email' => $user->email,
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





it('requires the user email for resetting password', function () {
    postJson('/api/user/password/forgot', [])
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
    postJson('/api/user/password/forgot', [
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





it('hits a validation error to find an existing user email for resetting password', function () {
    postJson('/api/user/password/forgot', [
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





it('hits a validation error to find an existing, but deleted user email for resetting password', function () {
    $user = User::factory()->create(['deleted_at' => now()])->refresh();

    postJson('/api/user/password/forgot', [
        'email' => $user->email,
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
    $user = User::factory()->create();

    $code = (string) mt_rand(000000, 999999);

    $config = config("auth.passwords.{$user->getMorphClass()}s");

    DB::table($config['table'])->insert([
        'email' => $user->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    postJson('/api/user/password/verify', [
        'email' => $user->email,
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
    postJson('/api/user/password/verify', [
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
    $user = User::factory()->create();

    postJson('/api/user/password/verify', [
        'email' => $user->email,
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
    $user = User::factory()->create();

    postJson('/api/user/password/verify', [
        'email' => $user->email,
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
    postJson('/api/user/password/verify', [
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





it('hits a validation error if user email does not exist to verify a password reset code', function () {
    postJson('/api/user/password/verify', [
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





it('hits a validation error if user email exists, but deleted to verify a password reset code', function () {
    $user = User::factory()->create(['deleted_at' => now()])->refresh();

    postJson('/api/user/password/verify', [
        'email' => $user->email,
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
    $user = User::factory()->create()->refresh();

    postJson('/api/user/password/verify', [
        'email' => $user->email,
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
    $user = User::factory()->create()->refresh();

    $code = (string) mt_rand(000000, 999999);

    $config = config("auth.passwords.{$user->getMorphClass()}s");

    DB::table($config['table'])->insert([
        'email' => $user->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    test()->travelTo(now()->addMinutes((int) config("auth.passwords.{$user->getMorphClass()}s.expire")));

    postJson('/api/user/password/verify', [
        'email' => $user->email,
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





it('requires the email to reset user password', function () {
    $password = Str::random(8);

    postJson('/api/user/password/reset', [
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





it('requires the code to reset user password', function () {
    $password = Str::random(8);
    $user = User::factory()->create();

    postJson('/api/user/password/reset', [
        'email' => $user->email,
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





it('requires the password to reset user password', function () {
    $user = User::factory()->create();

    postJson('/api/user/password/reset', [
        'email' => $user->email,
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





it('requires the password_confirmation to reset user password', function () {
    $user = User::factory()->create();
    $password = Str::random(8);

    postJson('/api/user/password/reset', [
        'email' => $user->email,
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





it('requires the password_confirmation to match the password to reset user password', function () {
    $user = User::factory()->create();
    $password = Str::random(8);

    postJson('/api/user/password/reset', [
        'email' => $user->email,
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





it('hits a validation error on invalid email to reset user password', function () {
    $password = Str::random(8);

    postJson('/api/user/password/reset', [
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





it('hits a validation error on non-existent email to reset user password', function () {
    $password = Str::random(8);

    postJson('/api/user/password/reset', [
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





it('hits a validation error on existent email, but deleted user to reset user password', function () {
    $password = Str::random(8);
    $user = User::factory()->create(['deleted_at' => now()]);

    postJson('/api/user/password/reset', [
        'email' => $user->email,
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





it('hits a validation error on invalid code as a string to reset user password', function () {
    $password = Str::random(8);
    $user = User::factory()->create();

    postJson('/api/user/password/reset', [
        'email' => $user->email,
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





it('can reset user password', function () {
    $password = Str::random(8);
    $user = User::factory()->create();
    $code = (string) mt_rand(000000, 999999);

    $config = config("auth.passwords.{$user->getMorphClass()}s");

    DB::table($config['table'])->insert([
        'email' => $user->email,
        'token' => Hash::make($code),
        'created_at' => now(),
    ]);

    postJson('/api/user/password/reset', [
        'email' => $user->email,
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

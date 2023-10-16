<?php

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'user');





it('can log user into the application', function () {
    $user = User::factory()->create()->refresh();

    postJson('/api/user/login', [
        'email' => $user->email,
        'password' => 'password',
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
                    ->where('locale', 'en')
                    ->where('code', 0)
                    ->where('message', trans('auth.success'))
                    ->has(
                        'data',
                        fn (AssertableJson $json) =>
                            $json->where('user.id', $user->id)
                                ->whereType('token', 'string')
                                ->where('requires_two_fa', false)
                    )
        );
});





it('can log user with two-fa into the application', function () {
    $user = User::factory()->twoFaEnabled()->create()->refresh();

    postJson('/api/user/login', [
        'email' => $user->email,
        'password' => 'password',
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
                    ->where('locale', 'en')
                    ->where('code', 0)
                    ->where('message', trans('auth.temp_success'))
                    ->has(
                        'data',
                        fn (AssertableJson $json) =>
                            $json->where('user.id', $user->id)
                                ->whereType('token', 'string')
                                ->where('requires_two_fa', true)
                    )
        );
});





it('hits a validation error if email is not supplied in request', function () {
    postJson('/api/user/login', [
        'password' => 'password',
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





it('hits a validation error if password is not supplied in request', function () {
    postJson('/api/user/login', [
        'email' => fake()->email(),
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





it('hits a validation error if email is not valid', function () {
    postJson('/api/user/login', [
        'email' => fake()->word(),
        'password' => 'password',
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





it('can logout an user', function () {
    sanctumLogin(User::factory()->create(), ['*'], 'api_user');

    postJson('/api/user/logout')
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
                    ->where('message', 'Logout was successful.')
                    ->whereType('data', 'null')
        );

    // NOT ADVISED: BUT NO VIABLE SOLUTION FROM THE LARAVEL CREATORS
    $this->app->get('auth')->forgetGuards();

    getJson('/api/user')->assertUnauthorized();
});





it('can logout a two-fa enabled user', function () {
    sanctumLogin(User::factory()->twoFaEnabled()->create(), ['two_fa'], 'api_user');

    postJson('/api/user/logout')
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
                    ->where('message', 'Logout was successful.')
                    ->whereType('data', 'null')
        );
});





it('can logout other devices for user', function () {
    $user = User::factory()->create();

    $user->createToken($user->getMorphClass());
    $user->createToken($user->getMorphClass());

    expect($user->tokens()->count())->toBe(2);

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/logout-others')
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
                    ->where('message', 'All other devices have been logged-out successfully.')
                    ->whereType('data', 'null')
        );

    expect($user->tokens()->count())->toBe(0);
});

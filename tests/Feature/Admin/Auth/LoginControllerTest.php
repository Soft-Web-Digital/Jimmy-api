<?php

use App\Enums\ApiErrorCode;
use App\Models\Admin;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'admin');





it('can log admin into the application', function () {
    $admin = Admin::factory()->create()->refresh();

    postJson('/api/admin/login', [
        'email' => $admin->email,
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
                            $json->where('admin', $admin->toArray())
                                ->whereType('token', 'string')
                                ->where('requires_two_fa', false)
                    )
        );
});





it('can log admin with two-fa into the application', function () {
    $admin = Admin::factory()->twoFaEnabled()->create()->refresh();

    postJson('/api/admin/login', [
        'email' => $admin->email,
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
                            $json->where('admin', $admin->toArray())
                                ->whereType('token', 'string')
                                ->where('requires_two_fa', true)
                    )
        );
});





it('hits a validation error if email is not supplied in request', function () {
    postJson('/api/admin/login', [
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
    postJson('/api/admin/login', [
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
    postJson('/api/admin/login', [
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





it('can logout an admin', function () {
    sanctumLogin(Admin::factory()->create(), ['*'], 'api_admin');

    postJson('/api/admin/logout')
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

    getJson('/api/admin')->assertUnauthorized();
});





it('can logout a two-fa enabled admin', function () {
    sanctumLogin(Admin::factory()->twoFaEnabled()->create(), ['two_fa'], 'api_admin');

    postJson('/api/admin/logout')
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





it('can logout other devices for admin', function () {
    $admin = Admin::factory()->create();

    $admin->createToken($admin->getMorphClass());
    $admin->createToken($admin->getMorphClass());

    expect($admin->tokens()->count())->toBe(2);

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/logout-others')
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

    expect($admin->tokens()->count())->toBe(0);
});

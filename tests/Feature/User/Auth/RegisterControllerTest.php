<?php

use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\postJson;

uses()->group('api', 'auth', 'user');





it('requires a valid country ID to register', function ($countryId) {
    postJson('/api/user/register', [
        'country_id' => $countryId,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('country_id', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json
                    ->where(
                        'data.errors.country_id.0',
                        trans('validation.' . ($countryId === null ? 'required' : 'exists'), [
                            'attribute' => 'country',
                        ])
                    )
                    ->etc()
        );
})->with([
    'empty value' => null,
    'random uuid' => fn () => fake()->uuid(),
    'deleted country ID' => fn () => Country::factory()->create(['deleted_at' => now()])->id,
    'country disabled for registration' => fn () => Country::factory()
        ->create(['registration_activated_at' => null])->id,
]);





it('requires a valid firstname', function ($firstname) {
    $message = match ($firstname) {
        null => trans('validation.required', ['attribute' => 'firstname']),
        1 => trans('validation.string', ['attribute' => 'firstname']),
        default => trans('validation.max.string', ['attribute' => 'firstname', 'max' => 191]),
    };

    postJson('/api/user/register', [
        'firstname' => $firstname,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('firstname', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.firstname.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'not string' => 1,
    'string too long' => str_repeat('a', 192),
]);





it('requires a valid lastname', function ($lastname) {
    $message = match ($lastname) {
        null => trans('validation.required', ['attribute' => 'lastname']),
        1 => trans('validation.string', ['attribute' => 'lastname']),
        default => trans('validation.max.string', ['attribute' => 'lastname', 'max' => 191]),
    };

    postJson('/api/user/register', [
        'lastname' => $lastname,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('lastname', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.lastname.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'not string' => 1,
    'string too long' => str_repeat('a', 192),
]);





it('required a valid email', function ($email) {
    $message = match ($email) {
        null => trans('validation.required', ['attribute' => 'email']),
        'invalid' => trans('validation.email', ['attribute' => 'email']),
        default => trans('validation.unique', ['attribute' => 'email']),
    };

    postJson('/api/user/register', [
        'country_id' => Country::factory()->create(['registration_activated_at' => now()])->id,
        'email' => $email,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.email.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'invalid email' => 'invalid',
    'existing email' => fn () => User::factory()->create()->email,
]);





it('requires a valid password and password confirmation', function ($data) {
    $response = postJson('/api/user/register', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('password', 'data.errors');

    if (count($data) < 1) {
        $response->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.password.0', trans('validation.required', [
                    'attribute' => 'password',
                ]))->etc()
        );
    }

    if (count($data) < 2 && isset($data['password_confirmation'])) {
        $response->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.password.0', trans('validation.required', [
                    'attribute' => 'password',
                ]))->etc()
        );
    }

    if (count($data) === 2) {
        $response->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.password.0', trans('validation.confirmed', [
                    'attribute' => 'password',
                ]))->etc()
        );
    }
})->with([
    'empty value' => fn () => [],
    'password only' => fn () => [
        'password' => 'password',
    ],
    'password_confirmation only' => fn () => [
        'password_confirmation' => 'password',
    ],
    'invalid password_confirmation' => fn () => [
        'password' => 'password',
        'password_confirmation' => 'passwordd',
    ],
]);





it('requires a valid username', function ($username) {
    $message = match ($username) {
        null => trans('validation.required', ['attribute' => 'username']),
        1 => trans('validation.string', ['attribute' => 'username']),
        str_repeat('a', 21) => trans('validation.max.string', ['attribute' => 'username', 'max' => '20']),
        default => trans('validation.unique', ['attribute' => 'username']),
    };

    postJson('/api/user/register', [
        'username' => $username,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('username', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.username.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'not string' => 1,
    'string too long' => str_repeat('a', 21),
    'existing username' => fn () => User::factory()->create()->username,
]);





it('requires a valid phone number for Nigeria', function ($phoneNumber) {
    $message = match ($phoneNumber) {
        null => trans('validation.required', ['attribute' => 'phone number']),
        default => trans('validation.phone', ['attribute' => 'phone number']),
    };

    postJson('/api/user/register', [
        'country_id' => Country::factory()->create(['alpha2_code' => 'NG', 'registration_activated_at' => now()])->id,
        'phone_number' => $phoneNumber,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('phone_number', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.phone_number.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'au number' => '1800 801 920',
    'za number' => '27810005933',
]);





it('can register a user', function () {
    postJson('/api/user/register', [
        'country_id' => Country::factory()->create(['alpha2_code' => 'NG', 'registration_activated_at' => now()])->id,
        'firstname' => fake()->firstName(),
        'lastname' => fake()->lastName(),
        'email' => fake()->email(),
        'password' => 'password',
        'password_confirmation' => 'password',
        'username' => fake()->userName(),
        'phone_number' => '07031111111',
    ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'User created successfully')
                    ->has(
                        'data',
                        fn (AssertableJson $json) =>
                            $json->whereType('token', 'string')
                                ->whereType('user', 'array')
                    )
        );
});

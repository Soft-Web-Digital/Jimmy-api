<?php

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'profile', 'user');
uses(RefreshDatabase::class);





it('gets the user profile and two-fa status', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Account fetched successfully')
                    ->has(
                        'data',
                        fn (AssertableJson $json) =>
                            $json->where('requires_two_fa', false)
                                ->where('user.id', $user->id)
                                ->has(
                                    'user.country',
                                    fn (AssertableJson $json) =>
                                        $json->hasAll('id', 'name', 'flag_url', 'dialing_code')
                                )
                    )
        );
});





it('gets the two-fa incomplete user profile', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['two_fa'], 'api_user');

    getJson('/api/user')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Account fetched successfully')
                    ->has(
                        'data',
                        fn (AssertableJson $json) =>
                            $json->where('requires_two_fa', true)
                                ->where('user.id', $user->id)
                                ->has(
                                    'user.country',
                                    fn (AssertableJson $json) =>
                                        $json->hasAll('id', 'name', 'flag_url', 'dialing_code')
                                )
                    )
        );
});





it('can update user password', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/profile/password', [
        'old_password' => 'password',
        'new_password' => 'passwordd',
        'new_password_confirmation' => 'passwordd',
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Profile password updated successfully')
                    ->whereType('data', 'null')
        );
});





it('verifies the old password on password update', function ($oldPassword) {
    $message = match ($oldPassword) {
        null => trans('validation.required', ['attribute' => 'old password']),
        default => trans('validation.current_password'),
    };

    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/profile/password', [
        'old_password' => $oldPassword,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('old_password', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.old_password.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'wrong password' => 'wrong password',
]);





it('toggles the user two-fa status ON', function () {
    $user = User::factory()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/profile/two-fa')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Two-FA status updated successfully')
                    ->has('data', fn ($json) => $json->where('status', true))
        );
});





it('toggles the user two-fa status OFF', function () {
    $user = User::factory()->twoFaEnabled()->create();

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/profile/two-fa')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Two-FA status updated successfully')
                    ->has('data', fn ($json) => $json->where('status', false))
        );
});





it('can update user with a valid country_id', function () {
    $user = User::factory()->create([
        'country_id' => Country::factory()->create(['alpha2_code' => 'US', 'registration_activated_at' => now()])->id,
    ])->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    $countryId = Country::factory()->create(['alpha2_code' => 'NG', 'registration_activated_at' => now()])->id;

    patchJson('/api/user/profile', [
        'country_id' => $countryId,
    ])
        ->assertOk()
        ->assertJsonFragment(['country_id' => $countryId]);
});





it('can update user with a valid firstname', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    $firstname = fake()->firstName();

    patchJson('/api/user/profile', [
        'firstname' => $firstname,
    ])
        ->assertOk()
        ->assertJsonFragment(['firstname' => $firstname]);
});





it('can update user with a valid lastname', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    $lastname = fake()->lastName();

    patchJson('/api/user/profile', [
        'lastname' => $lastname,
    ])
        ->assertOk()
        ->assertJsonFragment(['lastname' => $lastname]);
});





it('hits a validation error with a used email during update', function () {
    $email = User::factory()->create()->email;

    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    patchJson('/api/user/profile', [
        'email' => $email,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.email.0', trans('validation.unique', ['attribute' => 'email']))
                    ->etc()
        );
});





it('can update user with a valid email', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    $email = fake()->email();

    patchJson('/api/user/profile', [
        'email' => $email,
    ])
        ->assertOk()
        ->assertJsonFragment(['email' => $email]);
});





it('hits a validation error with a used username during update', function () {
    $username = User::factory()->create()->username;

    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    patchJson('/api/user/profile', [
        'username' => $username,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('username', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.username.0', trans('validation.unique', ['attribute' => 'username']))
                    ->etc()
        );
});





it('can update user with a valid username', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    $username = fake()->userName();

    patchJson('/api/user/profile', [
        'username' => $username,
    ])
        ->assertOk()
        ->assertJsonFragment(['username' => $username]);
});





it('can update user with a valid phone number', function () {
    $country = Country::factory()->create([
        'alpha2_code' => 'ng',
        'dialing_code' => '+234',
        'registration_activated_at' => now(),
    ])->refresh();

    $user = User::factory()->create([
        'country_id' => $country->id,
    ])->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    $phoneNumber = '7031111111';

    patchJson('/api/user/profile', [
        'phone_number' => $phoneNumber,
    ])
        ->assertOk()
        ->assertJsonFragment(['phone_number' => $phoneNumber]);
});





it('ignores update to avatar if it is a URL', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    patchJson('/api/user/profile', [
        'avatar' => fake()->imageUrl(),
    ])
        ->assertOk()
        ->assertJsonFragment(['avatar' => $user->avatar]);
});





it('can update user with a valid avatar', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    Storage::fake();

    $avatar = UploadedFile::fake()->image("{$user->id}.jpg");

    patchJson('/api/user/profile', [
        'avatar' => $avatar,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.user.avatar', $user->avatar)
                    ->etc()
        );

    Storage::assertExists("avatars/{$user->id}.jpg");
});





it('can update user with a valid date of birth', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    expect($user->date_of_birth)->toBeNull();

    patchJson('/api/user/profile', [
        'date_of_birth' => now()->subYears(10)->toDateString(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->whereType('data.user.date_of_birth', 'string')
                    ->etc()
        );
});





it('can delete user account', function () {
    $user = User::factory()->create()->refresh();

    sanctumLogin($user, ['*'], 'api_user');

    deleteJson('/api/user/profile', [
        'reason' => fake()->sentence(),
    ])
        ->assertNoContent();
});

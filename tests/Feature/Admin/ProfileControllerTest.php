<?php

use App\Enums\Permission as EnumsPermission;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'profile', 'admin');





it('gets the admin profile and two-fa status', function () {
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    getJson('/api/admin')
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
                                ->where('admin', $admin->toArray())
                                ->has(
                                    'admin.country',
                                    fn (AssertableJson $json) =>
                                        $json->hasAll('id', 'name', 'flag_url', 'dialing_code')
                                )
                    )
        );
});





it('gets the two-fa incomplete admin profile', function () {
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['two_fa'], 'api_admin');

    getJson('/api/admin')
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
                                ->where('admin', $admin->toArray())
                                ->has(
                                    'admin.country',
                                    fn (AssertableJson $json) =>
                                        $json->hasAll('id', 'name', 'flag_url', 'dialing_code')
                                )
                    )
        );
});





it("gets the admin's permissions", function () {
    test()->seed(PermissionSeeder::class);

    $admin = Admin::factory()->secure()->create();

    $admin->syncPermissions(Permission::query()->where('guard_name', 'api_admin')->get());

    sanctumLogin($admin, ['*'], 'api_admin');

    getJson('/api/admin/my-permissions')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Admin permissions fetched successfully.')
                    ->has(
                        'data.permissions',
                        Permission::query()->where('guard_name', 'api_admin')->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(['id', 'name', 'group_name', 'description'])
                    )
        );
});





it('can update admin password', function () {
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/profile/password', [
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

    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/profile/password', [
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





it('toggles the admin two-fa status ON', function () {
    $admin = Admin::factory()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/profile/two-fa')
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





it('toggles the admin two-fa status OFF', function () {
    $admin = Admin::factory()->twoFaEnabled()->create();

    sanctumLogin($admin, ['*'], 'api_admin');

    postJson('/api/admin/profile/two-fa')
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





it('can update admin with a valid country_id', function () {
    test()->seed(PermissionSeeder::class);

    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    $countryId = Country::factory()->create(['alpha2_code' => 'NG'])->id;

    patchJson('/api/admin/profile', [
        'country_id' => $countryId,
    ])
        ->assertOk()
        ->assertJsonFragment(['country_id' => $countryId]);
});





it('can update admin with a valid firstname', function () {
    test()->seed(PermissionSeeder::class);

    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    $firstname = fake()->firstName();

    patchJson('/api/admin/profile', [
        'firstname' => $firstname,
    ])
        ->assertOk()
        ->assertJsonFragment(['firstname' => $firstname]);
});





it('can update admin with a valid lastname', function () {
    test()->seed(PermissionSeeder::class);

    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    $lastname = fake()->lastName();

    patchJson('/api/admin/profile', [
        'lastname' => $lastname,
    ])
        ->assertOk()
        ->assertJsonFragment(['lastname' => $lastname]);
});





it('hits a validation error with a used email during update', function () {
    test()->seed(PermissionSeeder::class);

    $email = Admin::factory()->create()->email;

    $admin = Admin::factory()->secure()->create()->refresh();

    actingAsPermittedAdmin($admin, EnumsPermission::MANAGE_ADMINS);

    patchJson('/api/admin/profile', [
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





it('can update admin with a valid email', function () {
    test()->seed(PermissionSeeder::class);

    $admin = Admin::factory()->secure()->create()->refresh();

    actingAsPermittedAdmin($admin, EnumsPermission::MANAGE_ADMINS);

    $email = fake()->email();

    patchJson('/api/admin/profile', [
        'email' => $email,
    ])
        ->assertOk()
        ->assertJsonFragment(['email' => $email]);
});





it('can update admin with a valid phone number', function () {
    test()->seed(PermissionSeeder::class);

    $country = Country::factory()->create(['alpha2_code' => 'ng', 'dialing_code' => '+234'])->refresh();

    $admin = Admin::factory()->secure()->create(['country_id' => $country->id,])->refresh();

    actingAsPermittedAdmin($admin, EnumsPermission::MANAGE_ADMINS);

    $phoneNumber = '8135303377';

    patchJson('/api/admin/profile', [
        'phone_number' => $phoneNumber,
    ])
        ->assertOk()
        ->assertJsonFragment(['phone_number' => $phoneNumber]);
});





it('ignores update to avatar if it is a URL', function () {
    test()->seed(PermissionSeeder::class);

    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    patchJson('/api/admin/profile', [
        'avatar' => fake()->imageUrl(),
    ])
        ->assertOk()
        ->assertJsonFragment(['avatar' => $admin->avatar]);
});





it('can update admin with a valid avatar', function () {
    test()->seed(PermissionSeeder::class);

    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    Storage::fake();

    $avatar = UploadedFile::fake()->image("{$admin->id}.jpg");

    patchJson('/api/admin/profile', [
        'avatar' => $avatar,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.admin.avatar', $admin->avatar)
                    ->etc()
        );

    Storage::assertExists("avatars/{$admin->id}.jpg");
});

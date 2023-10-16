<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use App\Models\Admin;
use App\Models\Permission;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Laravel\Sanctum\Sanctum;
use Pest\PendingObjects\TestCall;

uses(Tests\TestCase::class)->in('Integration', 'Feature');
uses(RefreshDatabase::class)->in('Integration', 'Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Login a user.
 *
 * @param Authenticatable|null $user
 * @param string|null $guard
 * @return TestCall|TestCase|mixed
 */
function login(Authenticatable $user = null, string $guard = null)
{
    return test()->actingAs($user ?? User::factory()->create(), $guard);
}

/**
 * Login a user with Sanctum.
 *
 * @param Authenticatable|null $user
 * @param array<mixed, mixed> $abilities
 * @param string|null $guard
 * @return Authenticatable
 */
function sanctumLogin(Authenticatable $user = null, array $abilities = [], string $guard = null)
{
    return Sanctum::actingAs($user ?? User::factory()->create(), $abilities, $guard);
}

/**
 * Get user with an access token.
 *
 * @param Authenticatable|Admin|User $user
 * @param array<int, string> $abilities
 * @return Authenticatable|Admin|User $user
 */
function userWithAccessToken(Authenticatable $user, array $abilities = ['*'])
{
    $user->createToken($user->getMorphClass(), $abilities);

    return $user->withAccessToken(PersonalAccessToken::query()->latest()->first());
}

/**
 * Login an admin with the specified permission.
 *
 * @param Admin $admin
 * @param array<int, \App\Enums\Permission>|\App\Enums\Permission $permissions
 * @return Authenticatable
 */
function actingAsPermittedAdmin(Admin $admin, array|\App\Enums\Permission $permissions)
{
    test()->seed(PermissionSeeder::class);

    $admin->syncPermissions(
        Permission::query()
            ->where('guard_name', 'api_admin')
            ->when(
                is_array($permissions),
                fn ($query) => $query->whereIn('name', array_map(fn ($item) => $item->value, $permissions)),
                fn ($query) => $query->where('name', $permissions->value)
            )
            ->get()
    );

    return sanctumLogin($admin, ['*'], 'api_admin');
}

/**
 * Login an admin as super admin.
 *
 * @param Admin $admin
 * @return Authenticatable
 */
function actingAsSuperAdmin(Admin $admin): Authenticatable
{
    test()->seed(AdminSeeder::class);
    test()->seed(PermissionSeeder::class);

    $admin->assignRole(['SUPERADMIN']);

    return sanctumLogin($admin, ['*'], 'api_admin');
}

<?php

use App\DataTransferObjects\Models\RoleModelData;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ACL\RoleService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses()->group('service', 'acl');
uses(RefreshDatabase::class);





it('can seed the PermissionSeeder', function () {
    test()->seed(PermissionSeeder::class);

    expect(Permission::all())
        ->toBeCollection()->not->toBeEmpty()
        ->first()->name->toBeString();
});





it('can create a role', function ($guard) {
    $roleModelData = (new RoleModelData())
        ->setName(fake()->jobTitle())
        ->setDescription(fake()->sentence())
        ->setGuardName($guard);

    $role = (new RoleService())->create($roleModelData);

    expect($role)
        ->toBeInstanceOf(Role::class)
        ->name->toBe($roleModelData->getName())
        ->description->toBe($roleModelData->getDescription())
        ->guard_name->toBe($roleModelData->getGuardName());
})->with('guards');





it('can create a role with permissions', function ($guard) {
    Permission::factory()->guard($guard)->create();

    $roleModelData = (new RoleModelData())
        ->setName(fake()->jobTitle())
        ->setDescription(fake()->sentence())
        ->setGuardName($guard)
        ->setPermissions(
            Permission::query()->select('id')->where('guard_name', $guard)->pluck('id')->toArray()
        );

    (new RoleService())->create($roleModelData);

    expect(Role::with('permissions')->first())
        ->name->toBe($roleModelData->getName())
        ->description->toBe($roleModelData->getDescription())
        ->guard_name->toBe($roleModelData->getGuardName())
        ->permissions->toBeCollection()->not->toBeEmpty();
})->with('guards');





it('can throw an error exception', function ($guard) {
    $roleModelData = (new RoleModelData())
        ->setName(fake()->jobTitle())
        ->setDescription(fake()->sentence())
        ->setGuardName($guard)
        ->setPermissions([(object) ['data' => 1]]);

    (new RoleService())->create($roleModelData);
})->with('guards')->throws(\Error::class);





it('can rollback the database transaction on role creation', function ($guard) {
    Permission::factory()->guard($guard)->create();

    $roleModelData = (new RoleModelData())
        ->setName(fake()->jobTitle())
        ->setDescription(fake()->sentence())
        ->setGuardName($guard)
        ->setPermissions([DB::raw("['s']")]);

    try {
        (new RoleService())->create($roleModelData);
    } catch (\Exception $e) {
        expect($e)->toBeInstanceOf(QueryException::class);

        test()->assertDatabaseCount((new Role())->getTable(), 0);
    }
})->with('guards');





it('can update a role name', function ($guard) {
    $role = Role::factory()->guard($guard)->create();

    $roleModelData = (new RoleModelData())->setName(fake()->jobTitle());

    expect((new RoleService())->update($role, $roleModelData))
        ->toBeInstanceOf(Role::class)
        ->name->toBe($roleModelData->getName());
})->with('guards');





it('can update a role description', function ($guard) {
    $role = Role::factory()->guard($guard)->create();

    $roleModelData = (new RoleModelData())->setDescription(fake()->sentence());

    expect((new RoleService())->update($role, $roleModelData))
        ->toBeInstanceOf(Role::class)
        ->description->toBe($roleModelData->getDescription());
})->with('guards');





it('does not update a role name if not set in model data', function ($guard) {
    $role = Role::factory()->guard($guard)->create();

    $roleModelData = (new RoleModelData())->setDescription(fake()->sentence());

    expect((new RoleService())->update($role, $roleModelData))
        ->toBeInstanceOf(Role::class)
        ->name->toBe($role->name)
        ->description->toBe($roleModelData->getDescription());
})->with('guards');





it('does not update a role description if not set in model data', function ($guard) {
    $role = Role::factory()->guard($guard)->create();

    $roleModelData = (new RoleModelData())->setName(fake()->jobTitle());

    expect((new RoleService())->update($role, $roleModelData))
        ->toBeInstanceOf(Role::class)
        ->description->toBe($role->description)
        ->name->toBe($roleModelData->getName());
})->with('guards');





it('cannot update a role guard', function ($guard) {
    $role = Role::factory()->guard($guard)->create();

    $roleModelData = (new RoleModelData())->setGuardName('sample guard');

    expect((new RoleService())->update($role, $roleModelData))
        ->toBeInstanceOf(Role::class)
        ->guard_name->toBe($role->guard_name);
})->with('guards');





it('syncs new permissions during update', function ($guard) {
    $roleModelData = (new RoleModelData())
        ->setName(fake()->jobTitle())
        ->setDescription(fake()->sentence())
        ->setGuardName($guard)
        ->setPermissions([Permission::factory()->guard($guard)->create()->id]);

    $role = (new RoleService())->create($roleModelData);

    $oldPermissions = $role->permissions()->pluck('permission_id');

    $roleModelData->setPermissions([Permission::factory()->guard($guard)->create()->id]);

    $newRole = (new RoleService())->update($role, $roleModelData);

    $newPermissions = $newRole->permissions()->pluck('permission_id');

    expect($oldPermissions->diff($newPermissions)->isEmpty())->toBeFalse();
})->with('guards');





it('retains old permissions during update', function ($guard) {
    $roleModelData = (new RoleModelData())
        ->setName(fake()->jobTitle())
        ->setDescription(fake()->sentence())
        ->setGuardName($guard)
        ->setPermissions([Permission::factory()->guard($guard)->create()->id]);

    $role = (new RoleService())->create($roleModelData);

    $oldPermissions = $role->permissions()->pluck('permission_id');

    $roleModelData->setDescription(fake()->sentence());

    $newRole = (new RoleService())->update($role, $roleModelData);

    $newPermissions = $newRole->permissions()->pluck('permission_id');

    expect($oldPermissions->diff($newPermissions)->isEmpty())->toBeTrue();
})->with('guards');

<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Permission as ModelsPermission;
use App\Models\Role;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'acl');





it('rejects unpermitted admin from hitting the roles API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/roles{$path}")
        ->assertStatus(Response::HTTP_FORBIDDEN)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::GENERAL_ERROR->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
})->with([
    'index' => fn () => [
        'method' => 'GET',
        'path' => '',
    ],
    'store' => fn () => [
        'method' => 'POST',
        'path' => '',
    ],
    'show' => fn () => [
        'method' => 'GET',
        'path' => '/' . Role::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Role::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . Role::factory()->create()->id,
    ],
]);





it('get roles as an admin', function () {
    Role::factory()->guard('api_admin')->count(3)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    getJson('/api/admin/roles')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'roles' => [
                    'current_page',
                    'data',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ],
            ],
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Roles fetched successfully')
                    ->has(
                        'data.roles.data',
                        Role::query()->where('guard_name', 'api_admin')->where('name', '!=', 'SUPERADMIN')->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(Role::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('get roles in a non-paginated response', function () {
    Role::factory()->guard('api_admin')->count(3)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    getJson('/api/admin/roles?do_not_paginate=1')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Roles fetched successfully')
                    ->has(
                        'data.roles',
                        Role::query()->where('guard_name', 'api_admin')->where('name', '!=', 'SUPERADMIN')->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(Role::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('includes the query string in paginated roles list', function () {
    Role::factory()->count(10)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $query = http_build_query([
        'fields[roles]' => 'id',
        'per_page' => 5,
        'page' => 1,
    ]);

    getJson("/api/admin/roles?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.roles.first_page_url', url('api/admin/roles') . "?{$query}")
                    ->etc()
        );
});





it('shows a single role data', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $role = Role::factory()->guard('api_admin')->create()->refresh();

    getJson("/api/admin/roles/{$role->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Role fetched successfully.')
                    ->where('data.role', $role)
        );
});





it('requires a valid name to create a role', function ($name) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $message = match ($name) {
        null => trans('validation.required', ['attribute' => 'name']),
        1 => trans('validation.string', ['attribute' => 'name']),
        str_repeat('a', 21) => trans('validation.max.string', ['attribute' => 'name', 'max' => '20']),
        default => trans('validation.unique', ['attribute' => 'name']),
    };

    postJson('/api/admin/roles', [
        'name' => $name,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('name', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json
                    ->where('data.errors.name.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'number' => 1,
    'too long' => str_repeat('a', 21),
    'existing role' => fn () => Role::factory()->guard('api_admin')
        ->create(['name' => trim(substr(fake()->jobTitle(), 0, 20))])->name,
]);





it('may accept a valid description to create a role', function ($description) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $message = match ($description) {
        1 => trans('validation.string', ['attribute' => 'description']),
        default => trans('validation.max.string', ['attribute' => 'description', 'max' => '255']),
    };

    postJson('/api/admin/roles', [
        'description' => $description,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('description', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json
                    ->where('data.errors.description.0', $message)
                    ->etc()
        );
})->with([
    'number' => 1,
    'too long' => str_repeat('a', 256),
]);





it('requires at least one valid permission to create a role', function ($permissions) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    postJson('/api/admin/roles', [
        'permissions' => $permissions,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('permissions' . (!in_array($permissions, [null, 1]) ? '.0' : ''), 'data.errors');
})->with([
    'empty value' => null,
    'number' => 1,
    'invalid permission' => fn () => [fake()->uuid()],
]);





it('can create role', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    postJson('/api/admin/roles', [
        'name' => trim(substr(fake()->jobTitle(), 0, 20)),
        'description' => substr(fake()->sentence(), 0, 255),
        'permissions' => [ModelsPermission::factory()->guard('api_admin')->create()->id],
    ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Role created successfully')
                    ->where('data.role', Role::latest()->first())
        );
});





it('can update role name', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $role = Role::factory()->guard('api_admin')->create();

    $name = trim(substr(fake()->jobTitle(), 0, 20));

    patchJson("/api/admin/roles/{$role->id}", [
        'name' => $name,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.role.name', Role::latest()->where('guard_name', 'api_admin')->first()->name)
                    ->etc()
        );
});





it('can update role description', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $role = Role::factory()->guard('api_admin')->create();

    $description = substr(fake()->sentence(), 0, 255);

    patchJson("/api/admin/roles/{$role->id}", [
        'description' => $description,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where(
                    'data.role.description',
                    Role::latest()->where('guard_name', 'api_admin')->first()->description
                )
                    ->etc()
        );
});





it('can update role permissions', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $role = Role::factory()->guard('api_admin')->create();
    $role->syncPermissions(ModelsPermission::factory()->guard('api_admin')->create());

    $newPermissions = ModelsPermission::factory()->guard('api_admin')->count(2)->create();

    patchJson("/api/admin/roles/{$role->id}", [
        'permissions' => $newPermissions->pluck('id'),
    ])->assertOk();

    expect($role->load('permissions:id'))
        ->permissions->toHaveCount(2);
});





it('can delete role', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    $role = Role::factory()->create();

    deleteJson("/api/admin/roles/{$role->id}")->assertNoContent();

    expect(Role::find($role->id))->toBeNull();
});

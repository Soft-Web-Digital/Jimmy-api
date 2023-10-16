<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Permission as ModelsPermission;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'acl');





it('rejects unpermitted admin from hitting the permissions API', function () {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    getJson('/api/admin/permissions')
        ->assertStatus(Response::HTTP_FORBIDDEN)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::GENERAL_ERROR->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
});





it('get permissions as an admin', function () {
    ModelsPermission::factory()->guard('api_admin')->count(3)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    getJson('/api/admin/permissions')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'permissions' => [
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
                    ->where('message', 'Permissions fetched successfully.')
                    ->has(
                        'data.permissions.data',
                        ModelsPermission::where('guard_name', 'api_admin')->paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(ModelsPermission::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('get permissions in non-paginated response', function () {
    ModelsPermission::factory()->guard('api_admin')->count(3)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ACCESS_CONTROL_LIST);

    getJson('/api/admin/permissions?do_not_paginate=1')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Permissions fetched successfully.')
                    ->has(
                        'data.permissions',
                        ModelsPermission::query()->where('guard_name', 'api_admin')->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(ModelsPermission::query()->first()->toArray())->keys()->toArray())
                    )
        );
});

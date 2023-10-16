<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Role;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'admin');





it('rejects unpermitted admin from hitting the admins API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/admins{$path}")
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
        'path' => '/' . Admin::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Admin::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . Admin::factory()->create()->id,
    ],
    'restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Admin::factory()->deleted()->create()->id . '/restore',
    ],
    'toggle block' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Admin::factory()->create()->id . '/block',
    ],
    'toggle role' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Admin::factory()->create()->id . '/role',
    ],
]);





it('get admins as an admin', function () {
    Admin::factory()->count(3)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ADMINS);

    getJson('/api/admin/admins')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'admins' => [
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
                    ->where('message', 'Admins fetched successfully')
                    ->has(
                        'data.admins.data',
                        Admin::query()
                            ->whereHas('roles', fn ($query) => $query->where('name', '!=', 'SUPERADMIN'))
                            ->orWhereDoesntHave('roles')
                            ->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(Admin::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('get admins without the superadmin', function () {
    $admins = Admin::factory()->count(5)->create();

    $superadmin = $admins->first();
    $superadmin->assignRole(Role::factory()->guard('api_admin')->create(['name' => 'SUPERADMIN']));

    $loginAdmin = $admins->last();

    actingAsPermittedAdmin($loginAdmin, Permission::MANAGE_ADMINS);

    getJson('/api/admin/admins')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.admins.data',
                    Admin::query()
                        ->whereHas('roles', fn ($query) => $query->where('name', '!=', 'SUPERADMIN'))
                        ->orWhereDoesntHave('roles')
                        ->count(),
                    fn (AssertableJson $json) =>
                        $json->whereNot('id', $superadmin->id)
                            ->etc()
                )->etc()
        );
});





it('includes the query string in paginated admins list', function () {
    Admin::factory()->count(10)->create();

    $admin = Admin::factory()->secure()->create()->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_ADMINS);

    $query = http_build_query([
        'fields[admins]' => 'id',
        'per_page' => 5,
        'page' => 1,
    ]);

    getJson("/api/admin/admins?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.admins.first_page_url', url('api/admin/admins') . "?{$query}")
                    ->etc()
        );
});





it('selects only specified fields in admins list', function ($fields) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ADMINS);

    $query = http_build_query([
        'fields[admins]' => $fields,
    ]);

    getJson("/api/admin/admins?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.admins.data',
                    Admin::query()
                        ->whereHas('roles', fn ($query) => $query->where('name', '!=', 'SUPERADMIN'))
                        ->orWhereDoesntHave('roles')
                        ->count(),
                    fn (AssertableJson $json) =>
                        $json->hasAll(explode(',', $fields))
                )->etc()
        );
})->with([
    'id,country_id',
    'id,firstname',
    'id,lastname',
    'id,email,email_verified_at',
    'id,avatar,phone_number',
    'id,two_fa_activated_at,blocked_at,created_at,updated_at,deleted_at',
]);





it('filters the admin list by certain conditions', function ($data) {
    ['column' => $column, 'value' => $value] = $data;

    $countQuery = match ($column) {
        'email' => Admin::query()->where('email', 'LIKE', "%{$value}%"),
        'only_trashed' => Admin::withTrashed()->whereNotNull('deleted_at'),
        'with_trashed' => Admin::withTrashed(),
        'blocked' => Admin::query()->whereNotNull('blocked_at'),
        'unblocked' => Admin::query()->whereNull('blocked_at'),
        'email_verified' => Admin::query()->whereNotNull('email_verified_at'),
        'email_unverified' => Admin::query()->whereNull('email_verified_at'),
    };

    $sequence = match ($column) {
        'blocked', 'unblocked' => new \Illuminate\Database\Eloquent\Factories\Sequence(
            ['blocked_at' => now()],
            ['blocked_at' => null],
        ),
        'only_trashed', 'with_trashed' => new \Illuminate\Database\Eloquent\Factories\Sequence(
            ['deleted_at' => now()],
            ['deleted_at' => null],
        ),
        'email_verified', 'email_unverified' => new \Illuminate\Database\Eloquent\Factories\Sequence(
            ['email_verified_at' => now()],
            ['email_verified_at' => null],
        ),
        default => new \Illuminate\Database\Eloquent\Factories\Sequence([]),
    };

    if (str_contains($column, 'trashed')) {
        $column = 'trashed';
    }

    if (str_contains($column, 'unblocked')) {
        $column = 'blocked';
    }

    if (str_contains($column, 'email_unverified')) {
        $column = 'email_verified';
    }

    Admin::factory()->count(10)->state($sequence)->create();

    actingAsPermittedAdmin(Admin::factory()->create(), Permission::MANAGE_ADMINS);

    $query = http_build_query([
        "filter[{$column}]" => $value,
    ]);

    getJson("/api/admin/admins?{$query}")
        ->assertOk()
        ->assertJsonCount($countQuery->count(), 'data.admins.data');
})->with([
    'email' => fn () => [
        'column' => 'email',
        'value' => Admin::factory()->create()->email,
    ],
    'only_trashed' => fn () => [
        'column' => 'only_trashed',
        'value' => 'only',
    ],
    'with_trashed' => fn () => [
        'column' => 'with_trashed',
        'value' => 'with',
    ],
    'blocked' => fn () => [
        'column' => 'blocked',
        'value' => '1',
    ],
    'unblocked' => fn () => [
        'column' => 'unblocked',
        'value' => '0',
    ],
    'email_verified' => fn () => [
        'column' => 'email_verified',
        'value' => '1',
    ],
    'email_unverified' => fn () => [
        'column' => 'email_unverified',
        'value' => '0',
    ],
]);





it('shows a single admin data', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create()->refresh();

    getJson("/api/admin/admins/{$admin->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Admin fetched successfully')
                    ->where('data.admin', $admin)
        );
});





it('requires a valid country ID to create an admin', function ($countryId) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    postJson('/api/admin/admins', [
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
    'random uuid' => fn () => fake()->uuid(),
    'deleted country ID' => fn () => Country::factory()->create(['deleted_at' => now()])->id,
]);





it('requires a valid firstname to create an admin', function ($firstname) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $message = match ($firstname) {
        null => trans('validation.required', ['attribute' => 'firstname']),
        1 => trans('validation.string', ['attribute' => 'firstname']),
        default => trans('validation.max.string', ['attribute' => 'firstname', 'max' => 191]),
    };

    postJson('/api/admin/admins', [
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





it('requires a valid lastname to create an admin', function ($lastname) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $message = match ($lastname) {
        null => trans('validation.required', ['attribute' => 'lastname']),
        1 => trans('validation.string', ['attribute' => 'lastname']),
        default => trans('validation.max.string', ['attribute' => 'lastname', 'max' => 191]),
    };

    postJson('/api/admin/admins', [
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





it('requires a valid email to create an admin', function ($email) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $message = match ($email) {
        null => trans('validation.required', ['attribute' => 'email']),
        'invalid' => trans('validation.email', ['attribute' => 'email']),
        default => trans('validation.unique', ['attribute' => 'email']),
    };

    postJson('/api/admin/admins', [
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
    'existing email' => fn () => Admin::factory()->create()->email,
]);





it('may accept a valid login URL to create an admin', function ($loginUrl) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    postJson('/api/admin/admins', [
        'login_url' => $loginUrl,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where(
                    'data.errors.login_url.0',
                    trans('validation.url', [
                        'attribute' => 'login url',
                    ])
                )
                    ->etc()
        );
})->with([
    'number' => 1,
    'invalid url' => 'invalid',
]);





it('can create admin', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    test()->travel(5)->seconds();

    postJson('/api/admin/admins', [
        'country_id' => Country::factory()->create()->id,
        'firstname' => fake()->firstName(),
        'lastname' => fake()->lastName(),
        'email' => fake()->email(),
    ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Admin created successfully')
                    ->where('data.admin', Admin::latest()->first())
        );
});





it('can update admin with a valid country ID', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create();

    $countryId = Country::factory()->create()->id;

    patchJson("/api/admin/admins/{$admin->id}", [
        'country_id' => $countryId,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'country_id' => $countryId,
        ]);
});





it('cannot update admin with existing phone number with a new invalid country ID', function () {
    $loginAdmin = Admin::factory()->secure()
        ->create(['country_id' => Country::factory()->create(['alpha2_code' => 'US', 'alpha3_code' => 'USA'])->id]);

    actingAsPermittedAdmin($loginAdmin, Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create([
        'country_id' => Country::factory()->create(['alpha2_code' => 'NG', 'alpha3_code' => 'NGA'])->id,
        'phone_number' => '7031111111',
    ]);

    patchJson("/api/admin/admins/{$admin->id}", [
        'country_id' => $loginAdmin->country_id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('country_id', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.country_id.0', trans('validation.phone_country', [
                    'attribute' => 'country id',
                    'other' => 'phone number',
                ]))
                    ->etc()
        );
});





it('can update admin with a valid firstname', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create();

    $firstname = fake()->firstName();

    patchJson("/api/admin/admins/{$admin->id}", [
        'firstname' => $firstname,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'firstname' => $firstname,
        ]);
});





it('can update admin with a valid lastname', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create();

    $lastname = fake()->lastName();

    patchJson("/api/admin/admins/{$admin->id}", [
        'lastname' => $lastname,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'lastname' => $lastname,
        ]);
});





it('can update admin with a valid email', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create();

    $email = fake()->email();

    patchJson("/api/admin/admins/{$admin->id}", [
        'email' => $email,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'email' => $email,
        ]);
});





it('cannot update admin with an existing email', function () {
    $loginAdmin = actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create();

    patchJson("/api/admin/admins/{$admin->id}", [
        'email' => $loginAdmin->email,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('email', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.email.0', trans('validation.unique', [
                    'attribute' => 'email',
                ]))
                    ->etc()
        );
});





it('can delete an admin', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create()->refresh();

    deleteJson("/api/admin/admins/{$admin->id}")->assertNoContent();

    expect(Admin::find($admin->id))->toBeNull();
});





it('cannot delete a superadmin', function () {
    $superadmin = Admin::factory()->create();
    $superadmin->assignRole(Role::factory()->guard('api_admin')->create(['name' => 'SUPERADMIN']));

    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    deleteJson("/api/admin/admins/{$superadmin->id}")
        ->assertForbidden()
        ->assertJsonFragment(['message' => 'You are not authorized to manage superadmins']);
});





it('can restore an admin', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->deleted()->create()->refresh();

    patchJson("/api/admin/admins/{$admin->id}/restore")->assertOk();

    expect(Admin::find($admin->id))->not->toBeNull();
});





it('cannot restore a superadmin', function () {
    $superadmin = Admin::factory()->create();
    $superadmin->assignRole(Role::factory()->guard('api_admin')->create(['name' => 'SUPERADMIN']));

    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    patchJson("/api/admin/admins/{$superadmin->id}/restore")
        ->assertForbidden()
        ->assertJsonFragment(['message' => 'You are not authorized to manage superadmins']);
});





it('can toggle the blocked status of an admin', function ($status) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create(['blocked_at' => $status ? now() : null])->refresh();

    patchJson("/api/admin/admins/{$admin->id}/block")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Admin blocked status updated successfully')
                    ->whereType('data.admin.blocked_at', !$status ? 'string' : 'null')
        );
})->with([
    'ON' => fn () => true,
    'OFF' => fn () => false,
]);





it('cannot toggle the blocked status of a superadmin', function () {
    $superadmin = Admin::factory()->create();
    $superadmin->assignRole(Role::factory()->guard('api_admin')->create(['name' => 'SUPERADMIN']));

    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    patchJson("/api/admin/admins/{$superadmin->id}/block")
        ->assertForbidden()
        ->assertJsonFragment(['message' => 'You are not authorized to manage superadmins']);
});





it('can toggle the role of an admin', function ($status) {
    $role = Role::factory()->guard('api_admin')->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    $admin = Admin::factory()->create()->refresh();

    if ($status) {
        $admin->assignRole($role);
    }

    patchJson("/api/admin/admins/{$admin->id}/role", [
        'role_id' => $role->id,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Admin role updated successfully.')
                    ->etc()
        );

    expect($admin->refresh()->hasRole($role))->toBe(!$status);
})->with([
    'ON' => fn () => true,
    'OFF' => fn () => false,
]);





it('cannot toggle the role of a superadmin', function () {
    $superadmin = Admin::factory()->create();
    $superadmin->assignRole(Role::factory()->guard('api_admin')->create(['name' => 'SUPERADMIN']));

    $role = Role::factory()->guard('api_admin')->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ADMINS);

    patchJson("/api/admin/admins/{$superadmin->id}/role", [
        'role_id' => $role->id,
    ])
        ->assertForbidden()
        ->assertJsonFragment(['message' => 'You are not authorized to manage superadmins']);
});

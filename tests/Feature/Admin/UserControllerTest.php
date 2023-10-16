<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Enums\WalletTransactionType;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'user');





it('rejects unpermitted admin from getting users', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/users{$path}")
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
    'users index' => fn () => [
        'method' => 'GET',
        'path' => '',
    ],
    'users show' => fn () => [
        'method' => 'GET',
        'path' => '/' . User::factory()->create()->id,
    ],
    'users block' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . User::factory()->create()->id . '/block',
    ],
    'users finance' => fn () => [
        'method' => 'POST',
        'path' => '/' . User::factory()->create()->id . '/finance/' . WalletTransactionType::random()->value,
    ],
]);





it('rejects unpermitted admin from finance users', function ($route) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_USERS);

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/users{$path}")
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
    'users finance' => fn () => [
        'method' => 'POST',
        'path' => '/' . User::factory()->create()->id . '/finance/' . WalletTransactionType::random()->value,
    ],
]);





it('can view all users', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_USERS);

    User::factory()->count(5)->create();

    getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'users' => [
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
                    ->where('message', 'Users fetched successfully.')
                    ->has(
                        'data.users.data',
                        User::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(User::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('selects only specified fields in users list', function ($fields) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_USERS);

    User::factory()->count(10)->create();

    $query = http_build_query([
        'fields[users]' => $fields,
    ]);

    getJson("/api/admin/users?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.users.data',
                    User::paginate()->count(),
                    fn (AssertableJson $json) =>
                        $json->hasAll(explode(',', $fields))
                )->etc()
        );
})->with([
    'id,firstname,lastname',
    'id,email,username,phone_number',
    'id,transaction_pin_set,transaction_pin_activated_at',
    'id,created_at,updated_at,deleted_at,deleted_reason',
]);





it('can view a single user', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_USERS);

    $users = User::factory()->count(10)->create();
    $user = $users->first()->refresh();

    getJson("/api/admin/users/{$user->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'User fetched successfully.')
                    ->has('data.user')
        );
});





it('can toggle the blocked status of a user', function ($user) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_USERS);

    patchJson("/api/admin/users/{$user->id}/block")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->whereType('data.user.blocked_at', $user->blocked_at ? 'null' : 'string')
                    ->etc()
        );
})->with([
    'blocked' => fn () => User::factory()->blocked()->create()->refresh(),
    'unblocked' => fn () => User::factory()->create()->refresh(),
]);





it('can restore a user', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_USERS);

    $user = User::factory()->deleted()->create()->refresh();

    patchJson("/api/admin/users/{$user->id}/restore")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.user.deleted_at', null)
                    ->where('data.user.deleted_reason', null)
                    ->etc()
        );
});





it('can dynamically finanace users', function ($type) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), [
        Permission::MANAGE_USERS,
        Permission::FINANCE_USERS
    ]);

    $amount = 5000;

    $walletTransactionType = WalletTransactionType::from($type);

    $user = User::factory()->create(
        $walletTransactionType === WalletTransactionType::DEBIT
            ? ['wallet_balance' => $amount * 2]
            : []
    );

    postJson("/api/admin/users/{$user->id}/finance/{$type}", [
        'amount' => $amount,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('message', "NGN {$amount} {$walletTransactionType->sentenceTerm()} user successfully.")
                    ->etc()
        );
})->with(WalletTransactionType::values());




it('can export users', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_USERS);

    User::factory()->count(5)->create();

    Excel::fake();

    getJson('/api/admin/users/export')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
            $json->where('data.path', asset(Storage::url('exports/users.xlsx')))
                ->etc()
        );

    Excel::assertStored('exports/users.xlsx', 'public');
});

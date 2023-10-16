<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Network;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'network');





it('rejects unpermitted admin from hitting the network APIs', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/networks{$path}")
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
        'path' => '/',
    ],
    'store' => fn () => [
        'method' => 'POST',
        'path' => '/',
    ],
    'show' => fn () => [
        'method' => 'GET',
        'path' => '/' . Network::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Network::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . Network::factory()->create()->id,
    ],
    'restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Network::factory()->create()->id . '/restore',
    ],
]);





it('can get all networks in a paginated format', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_NETWORKS);

    Network::factory()->create();

    getJson('/api/admin/networks')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'networks' => [
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
                    ->where('message', 'Networks fetched successfully')
                    ->has(
                        'data.networks.data',
                        Network::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(Network::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('can create a new network', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_NETWORKS);

    postJson('/api/admin/networks', [
        'name' => fake()->domainWord(),
        'wallet_address' => fake()->unique()->iban(prefix: '0X'),
        'comment' => fake()->sentence(),
    ])
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Network created successfully')
                    ->whereType('data.network', 'array')
        );
});





it('can get a single network', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_NETWORKS);

    $network = Network::factory()->create();

    getJson("/api/admin/networks/{$network->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Network fetched successfully')
                    ->where('data.network.id', $network->id)
                    ->etc()
        );
});





it('can update an existing network', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_NETWORKS);

    $network = Network::factory()->create();

    patchJson("/api/admin/networks/{$network->id}", [
        'name' => $name = fake()->domainName(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Network updated successfully')
                    ->where('data.network.id', $network->id)
                    ->where('data.network.name', $name)
                    ->etc()
        );
});





it('can delete a network', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_NETWORKS);

    $network = Network::factory()->create();
    expect($network->deleted_at)->toBeNull();

    deleteJson("/api/admin/networks/{$network->id}")->assertNoContent();

    expect($network->refresh()->deleted_at)->not->toBeNull();
});





it('can restore a network', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_NETWORKS);

    $network = Network::factory()->deleted()->create()->refresh();
    expect($network->deleted_at)->not->toBeNull();

    patchJson("/api/admin/networks/{$network->id}/restore")->assertOk();

    expect($network->refresh()->deleted_at)->toBeNull();
});

<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Asset;
use App\Models\Network;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'asset');





it('rejects unpermitted admin from hitting the asset API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/assets{$path}")
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
        'path' => '/' . Asset::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Asset::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . Asset::factory()->create()->id,
    ],
    'restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Asset::factory()->create()->id . '/restore',
    ],
]);





it('can get all assets in a paginated format', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSETS);

    Asset::factory()->create();

    getJson('/api/admin/assets')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'assets' => [
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
                    ->where('message', 'Assets fetched successfully')
                    ->has(
                        'data.assets.data',
                        Asset::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(Asset::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('can create a new asset', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSETS);

    $networks = Network::factory()->count(2)->create()->pluck('id')->toArray();

    Storage::fake();

    postJson('/api/admin/assets', [
        'name' => fake()->domainWord(),
        'code' => fake()->currencyCode(),
        'icon' => UploadedFile::fake()->image('icon.jpg'),
        'buy_rate' => fake()->randomFloat(),
        'sell_rate' => fake()->randomFloat(),
        'networks' => $networks,
        'buy_min_amount' => fake()->randomFloat(),
        'buy_max_amount' => fake()->randomFloat(),
        'sell_min_amount' => fake()->randomFloat(),
        'sell_max_amount' => fake()->randomFloat(),
    ])
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset created successfully')
                    ->whereType('data.asset', 'array')
        );
});





it('can get a single asset', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSETS);

    $asset = Asset::factory()->create()->refresh();

    getJson("/api/admin/assets/{$asset->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset fetched successfully')
                    ->where('data.asset.id', $asset->id)
                    ->where('data.asset.code', $asset->code)
                    ->where('data.asset.name', $asset->name)
                    ->etc()
        );
});





it('can update an asset', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSETS);

    $asset = Asset::factory()->create();

    patchJson("/api/admin/assets/{$asset->id}", [
        'name' => $name = fake()->domainName(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset updated successfully')
                    ->where('data.asset.id', $asset->id)
                    ->where('data.asset.name', $name)
                    ->etc()
        );
});





it('can delete an asset', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSETS);

    $asset = Asset::factory()->create();
    expect($asset->deleted_at)->toBeNull();

    deleteJson("/api/admin/assets/{$asset->id}")->assertNoContent();

    expect($asset->refresh()->deleted_at)->not->toBeNull();
});





it('can restore an asset', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSETS);

    $asset = Asset::factory()->deleted()->create();
    expect($asset->deleted_at)->not->toBeNull();

    patchJson("/api/admin/assets/{$asset->id}/restore")->assertOk();

    expect($asset->refresh()->deleted_at)->toBeNull();
});

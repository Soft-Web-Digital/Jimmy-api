<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Banner;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'banner');





it('rejects unpermitted admin from hitting the banners API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/banners{$path}")
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
        'path' => '/' . Banner::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . Banner::factory()->create()->id,
    ],
]);





it('can get banners', function () {
    Banner::factory()->count(3)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_BANNERS);

    getJson('/api/admin/banners')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'banners' => [
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
                    ->where('message', 'Banners fetched successfully')
                    ->has(
                        'data.banners.data',
                        Banner::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(Banner::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('can create a new banner', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_BANNERS);

    Storage::fake();

    postJson('/api/admin/banners', [
        'preview_image' => UploadedFile::fake()->image('banner.jpg', 100, 100),
        'featured_image' => UploadedFile::fake()->image('banner.jpg', 300, 300),
    ])
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Banner created successfully')
                    ->whereType('data.banner', 'array')
        );
});





it('can get a single banner', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_BANNERS);

    $banner = Banner::factory()->create()->refresh();

    getJson("/api/admin/banners/{$banner->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Banner fetched successfully')
                    ->where('data.banner.id', $banner->id)
                    ->where('data.banner.preview_image', $banner->preview_image)
                    ->where('data.banner.featured_image', $banner->featured_image)
                    ->etc()
        );
});





it('toggles banner activation status', function ($status) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_BANNERS);

    $banner = Banner::factory()->create(['activated_at' => $status ? now() : null])->refresh();

    patchJson("/api/admin/banners/{$banner->id}/activation")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Banner activation status updated successfully')
                    ->whereNot('data.banner.activated_at', $banner->activated_at)
                    ->whereType('data.banner.activated_at', !$status ? 'string' : 'null')
        );
})->with([
    'ON' => fn () => true,
    'OFF' => fn () => false,
]);





it('can delete a banner', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_BANNERS);

    $banner = Banner::factory()->create();

    deleteJson("/api/admin/banners/{$banner->id}")->assertNoContent();
});

<?php

use App\Enums\ApiErrorCode;
use App\Enums\GiftcardServiceProvider;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Country;
use App\Models\GiftcardCategory;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'giftcard-category');





it('rejects unpermitted admin from hitting the giftcard category API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/giftcard-categories{$path}")
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
        'path' => '/' . GiftcardCategory::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . GiftcardCategory::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . GiftcardCategory::factory()->create()->id,
    ],
    'restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . GiftcardCategory::factory()->create()->id . '/restore',
    ],
    'toggle sale activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . GiftcardCategory::factory()->create()->id . '/sale-activation',
    ],
    'toggle purchase activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . GiftcardCategory::factory()->create()->id . '/purchase-activation',
    ],
]);





it('can get the giftcard categories in a paginated format', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    GiftcardCategory::factory()->create();

    getJson('/api/admin/giftcard-categories')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'giftcard_categories' => [
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
                    ->where('message', 'Giftcard categories fetched successfully')
                    ->has(
                        'data.giftcard_categories.data',
                        GiftcardCategory::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(GiftcardCategory::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('can create a giftcard category', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    Storage::fake();

    postJson('/api/admin/giftcard-categories', [
        'name' => fake()->unique()->company(),
        'icon' => UploadedFile::fake()->image('icon.jpg'),
        'sale_term' => fake()->sentence(),
    ])
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard category created successfully')
                    ->whereType('data.giftcard_category', 'array')
        );
});





it('can get a single giftcard category', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    $giftcardCategory = GiftcardCategory::factory()->create()->refresh();

    getJson("/api/admin/giftcard-categories/{$giftcardCategory->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard category fetched successfully')
                    ->where('data.giftcard_category', $giftcardCategory->toArray())
        );
});





it('can update a giftcard category', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    $giftcardCategory = GiftcardCategory::factory()->create()->refresh();

    Storage::fake();

    $countries = Country::factory()->count(2)->create(['giftcard_activated_at' => now()])->pluck('id')->toArray();

    patchJson("/api/admin/giftcard-categories/{$giftcardCategory->id}", [
        'countries' => $countries,
        'icon' => UploadedFile::fake()->image('icon.jpg'),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard category updated successfully')
                    ->etc()
        );
});





it('can toggle the sale activation of a giftcard category', function ($status) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    $giftcardCategory = GiftcardCategory::factory()->create(['sale_activated_at' => $status])->refresh();

    patchJson("/api/admin/giftcard-categories/{$giftcardCategory->id}/sale-activation")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard category sale activation updated successfully')
                    ->whereType('data.giftcard_category.sale_activated_at', is_null($status) ? 'string' : 'null')
                    ->etc()
        );
})->with([
    'activated' => fn () => now(),
    'deactivated' => fn () => null,
]);





it('can toggle the purchase activation of a giftcard category', function ($status) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    $giftcardCategory = GiftcardCategory::factory()->create([
        'purchase_activated_at' => $status,
        'service_provider' => GiftcardServiceProvider::WAVERLITE->value,
    ])->refresh();

    patchJson("/api/admin/giftcard-categories/{$giftcardCategory->id}/purchase-activation")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard category purchase activation updated successfully')
                    ->whereType('data.giftcard_category.purchase_activated_at', is_null($status) ? 'string' : 'null')
                    ->etc()
        );
})->with([
    'activated' => fn () => now(),
    'deactivated' => fn () => null,
]);





it('can delete a giftcard category', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    $giftcardCategory = GiftcardCategory::factory()->create();

    deleteJson("/api/admin/giftcard-categories/{$giftcardCategory->id}")->assertNoContent();

    expect($giftcardCategory->refresh()->deleted_at)->not->toBeNull();
});





it('can restore a giftcard category', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_CATEGORIES);

    $giftcardCategory = GiftcardCategory::factory()->deleted()->create();

    patchJson("/api/admin/giftcard-categories/{$giftcardCategory->id}/restore")->assertOk();

    expect($giftcardCategory->refresh()->deleted_at)->toBeNull();
});

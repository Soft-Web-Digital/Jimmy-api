<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Currency;
use App\Models\GiftcardCategory;
use App\Models\GiftcardProduct;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'giftcard-product');





it('rejects unpermitted admin from hitting the giftcard product API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/giftcard-products{$path}")
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
        'path' => '/' . GiftcardProduct::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . GiftcardProduct::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . GiftcardProduct::factory()->create()->id,
    ],
    'restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . GiftcardProduct::factory()->create()->id . '/restore',
    ],
    'toggle activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . GiftcardProduct::factory()->create()->id . '/activation',
    ],
]);





it('can get the giftcard products in a paginated format', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_PRODUCTS);

    GiftcardProduct::factory()->create();

    getJson('/api/admin/giftcard-products')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'giftcard_products' => [
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
                    ->where('message', 'Giftcard products fetched successfully')
                    ->has(
                        'data.giftcard_products.data',
                        GiftcardProduct::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(GiftcardProduct::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('can create a giftcard product', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_PRODUCTS);

    $country = Country::factory()->create();
    $giftcardCategory = GiftcardCategory::factory()->saleActivated()->create();
    $giftcardCategory->countries()->sync($country->id);

    postJson('/api/admin/giftcard-products', [
        'giftcard_category_id' => $giftcardCategory->id,
        'country_id' => $country->id,
        'currency_id' => Currency::factory()->create()->id,
        'name' => fake()->company(),
        'sell_rate' => fake()->randomFloat(min: 10),
        'sell_min_amount' => 0,
        'sell_max_amount' => 1,
    ])
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard product created successfully')
                    ->whereType('data.giftcard_product', 'array')
        );
});





it('can get a single giftcard product', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_PRODUCTS);

    $giftcardProduct = GiftcardProduct::factory()->create()->refresh();

    getJson("/api/admin/giftcard-products/{$giftcardProduct->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard product fetched successfully')
                    ->where('data.giftcard_product.id', $giftcardProduct->id)
                    ->etc()
        );
});





it('can update a giftcard product', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_PRODUCTS);

    $giftcardProduct = GiftcardProduct::factory()->create()->refresh();

    patchJson("/api/admin/giftcard-products/{$giftcardProduct->id}", [
        'name' => $name = fake()->company(),
        'sell_rate' => 500,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'name' => $name,
            'sell_rate' => 500.0,
        ]);
});





it('can toggle the activation of a giftcard product', function ($status) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_PRODUCTS);

    $giftcardProduct = GiftcardProduct::factory()->create(['activated_at' => $status])->refresh();

    patchJson("/api/admin/giftcard-products/{$giftcardProduct->id}/activation")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard product activation updated successfully')
                    ->whereType('data.giftcard_product.activated_at', is_null($status) ? 'string' : 'null')
                    ->etc()
        );
})->with([
    'activated' => fn () => now(),
    'deactivated' => fn () => null,
]);





it('can delete a giftcard product', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_PRODUCTS);

    $giftcardProduct = GiftcardProduct::factory()->create();

    deleteJson("/api/admin/giftcard-products/{$giftcardProduct->id}")->assertNoContent();

    expect($giftcardProduct->refresh()->deleted_at)->not->toBeNull();
});





it('can restore a giftcard product', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARD_PRODUCTS);

    $giftcardProduct = GiftcardProduct::factory()->deleted()->create();

    patchJson("/api/admin/giftcard-products/{$giftcardProduct->id}/restore")->assertOk();

    expect($giftcardProduct->refresh()->deleted_at)->toBeNull();
});

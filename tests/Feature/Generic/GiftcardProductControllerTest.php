<?php

use App\Models\GiftcardCategory;
use App\Models\GiftcardProduct;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'giftcard-product');





it('can get giftcard products in a paginated format', function () {
    GiftcardProduct::factory()->count(5)
        ->for(GiftcardCategory::factory()->saleActivated())
        ->create(['activated_at' => now()]);

    getJson('/api/giftcard-products')
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
                            $json->hasAll([
                                'id',
                                'giftcard_category_id',
                                'country_id',
                                'currency_id',
                                'name',
                                'sell_rate',
                                'sell_min_amount',
                                'sell_max_amount',
                                'buy_min_amount',
                                'buy_max_amount',
                                'activated_at',
                            ])
                    )
        );
});





it('can get all giftcard products in a non-paginated format', function () {
    GiftcardProduct::factory()->count(5)
        ->for(GiftcardCategory::factory()->saleActivated())
        ->create(['activated_at' => now()]);

    getJson('/api/giftcard-products?do_not_paginate=1')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard products fetched successfully')
                    ->has(
                        'data.giftcard_products',
                        GiftcardProduct::count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'giftcard_category_id',
                                'country_id',
                                'currency_id',
                                'name',
                                'sell_rate',
                                'sell_min_amount',
                                'sell_max_amount',
                                'buy_min_amount',
                                'buy_max_amount',
                                'activated_at',
                            ])
                    )
        );
});

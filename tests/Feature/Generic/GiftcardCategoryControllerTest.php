<?php

use App\Models\GiftcardCategory;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'giftcard-category');





it('can get giftcard categories in a paginated format', function () {
    GiftcardCategory::factory()->count(5)->create();

    getJson('/api/giftcard-categories')
        ->assertOk()
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
                            $json->hasAll([
                                'id',
                                'name',
                                'icon',
                                'sale_term',
                                'purchase_term',
                                'sale_activated_at',
                                'purchase_activated_at',
                            ])
                    )
        );
});





it('can get all giftcard categories in a non-paginated format', function () {
    GiftcardCategory::factory()->count(5)->create();

    getJson('/api/giftcard-categories?do_not_paginate=1')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard categories fetched successfully')
                    ->has(
                        'data.giftcard_categories',
                        GiftcardCategory::count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'name',
                                'icon',
                                'sale_term',
                                'purchase_term',
                                'sale_activated_at',
                                'purchase_activated_at',
                            ])
                    )
        );
});

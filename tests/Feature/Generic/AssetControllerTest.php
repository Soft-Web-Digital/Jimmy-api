<?php

use App\Models\Asset;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'asset');










it('can get the assets', function () {
    Asset::factory()->count(20)->create();

    getJson('/api/assets')
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
                    ->where('message', 'Assets fetched successfully.')
                    ->has(
                        'data.assets.data',
                        Asset::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'code',
                                'name',
                                'icon',
                                'buy_rate',
                                'sell_rate',
                                'sell_min_amount',
                                'sell_max_amount',
                                'buy_min_amount',
                                'buy_max_amount',
                            ])
                    )
        );
});





it('fetch the all assets from storage without pagination', function () {
    Asset::factory()->count(20)->create();

    $queryString = '?do_not_paginate=1';

    getJson("/api/assets{$queryString}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Assets fetched successfully.')
                    ->has(
                        'data.assets',
                        Asset::count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'code',
                                'name',
                                'icon',
                                'buy_rate',
                                'sell_rate',
                                'sell_min_amount',
                                'sell_max_amount',
                                'buy_min_amount',
                                'buy_max_amount',
                            ])
                    )
        );
});





it('selects only specified fields in assets list', function ($fields) {
    Asset::factory()->create();

    $query = http_build_query([
        'fields[assets]' => $fields,
    ]);

    getJson("/api/assets?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.assets.data',
                    Asset::paginate()->count(),
                    fn (AssertableJson $json) =>
                        $json->hasAll(explode(',', $fields))
                )->etc()
        );
})->with([
    'id,name',
    'id,name,icon',
    'id,name,code,icon',
    'id,name,code,icon,buy_rate',
    'id,name,code,icon,sell_rate',
    'id,name,code,icon,sell_min_amount',
    'id,name,code,icon,sell_max_amount',
    'id,name,code,icon,buy_min_amount',
    'id,name,code,icon,buy_max_amount',
]);





it('cannot select unconfigured fields in assets list', function ($fields) {
    Asset::factory()->create();

    $query = http_build_query([
        'fields[assets]' => $fields,
    ]);

    getJson("/api/assets?{$query}")->assertStatus(Response::HTTP_BAD_REQUEST);
})->with([
    'id,created_at',
]);

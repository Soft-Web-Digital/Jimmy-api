<?php

use App\Models\Network;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'network');










it('can get the networks', function () {
    Network::factory()->count(20)->create();

    getJson('/api/networks')
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
                    ->where('message', 'Networks fetched successfully.')
                    ->has(
                        'data.networks.data',
                        Network::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'name',
                                'wallet_address',
                                'comment',
                            ])
                    )
        );
});





it('fetch the all networks from storage without pagination', function () {
    Network::factory()->count(20)->create();

    $queryString = '?do_not_paginate=1';

    getJson("/api/networks{$queryString}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Networks fetched successfully.')
                    ->has(
                        'data.networks',
                        Network::count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'name',
                                'wallet_address',
                                'comment',
                            ])
                    )
        );
});





it('selects only specified fields in networks list', function ($fields) {
    Network::factory()->create();

    $query = http_build_query([
        'fields[networks]' => $fields,
    ]);

    getJson("/api/networks?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.networks.data',
                    Network::paginate()->count(),
                    fn (AssertableJson $json) =>
                        $json->hasAll(explode(',', $fields))
                )->etc()
        );
})->with([
    'id,name',
    'id,name,wallet_address',
    'id,name,wallet_address,comment',
]);





it('cannot select unconfigured fields in networks list', function ($fields) {
    Network::factory()->create();

    $query = http_build_query([
        'fields[networks]' => $fields,
    ]);

    getJson("/api/networks?{$query}")->assertStatus(Response::HTTP_BAD_REQUEST);
})->with([
    'id,created_at',
]);

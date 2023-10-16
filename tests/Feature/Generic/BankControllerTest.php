<?php

use App\Models\Bank;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'bank');










it('can get the banks', function () {
    Bank::factory()->count(20)->create();

    getJson('/api/banks')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'banks' => [
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
                    ->where('message', 'Banks fetched successfully.')
                    ->has(
                        'data.banks.data',
                        Bank::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'name',
                            ])
                    )
        );
});





it('fetch the all banks from storage without pagination', function () {
    Bank::factory()->count(20)->create();

    $queryString = '?do_not_paginate=1';

    getJson("/api/banks{$queryString}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Banks fetched successfully.')
                    ->has(
                        'data.banks',
                        Bank::count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'name',
                            ])
                    )
        );
});





it('selects only specified fields in banks list', function ($fields) {
    Bank::factory()->create();

    $query = http_build_query([
        'fields[banks]' => $fields,
    ]);

    getJson("/api/banks?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.banks.data',
                    Bank::paginate()->count(),
                    fn (AssertableJson $json) =>
                        $json->hasAll(explode(',', $fields))
                )->etc()
        );
})->with([
    'id,name',
    'id,name,country_id',
]);





it('cannot select unconfigured fields in banks list', function ($fields) {
    Bank::factory()->create();

    $query = http_build_query([
        'fields[banks]' => $fields,
    ]);

    getJson("/api/banks?{$query}")->assertStatus(Response::HTTP_BAD_REQUEST);
})->with([
    'id,created_at',
]);

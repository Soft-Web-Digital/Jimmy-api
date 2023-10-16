<?php

use App\Models\Currency;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'currency');





it('can get the currencies', function () {
    getJson('/api/currencies')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'currencies' => [
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
                    ->where('message', 'Currencies fetched successfully')
                    ->whereType('data', 'array')
        );
});





it('includes the query string for paginated response', function () {
    getJson('/api/currencies')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.currencies.first_page_url', url('api/currencies') . '?page=1')
                    ->etc()
        );
});





it('includes the query string with a filter for paginated response', function () {
    $query = http_build_query([
        'fields[currencies]' => 'id',
        'page' => 1,
    ]);

    getJson("/api/currencies?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.currencies.first_page_url', url('api/currencies') . "?{$query}")
                    ->etc()
        );
});





it('fetch the all currencies from storage without pagination', function () {
    $queryString = '?do_not_paginate=1';

    getJson("/api/currencies{$queryString}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'currencies',
            ],
        ])
        ->assertJsonCount(Currency::count(), 'data.currencies');
});





it('selects the id, name and code of each currency by default', function () {
    Currency::factory()->count(5)->create();

    getJson('/api/currencies?do_not_paginate=1')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has('data.currencies', Currency::count())
                    ->has(
                        'data.currencies.0',
                        fn (AssertableJson $json) => $json->hasAll([
                            'id',
                            'name',
                            'code',
                            'exchange_rate_to_ngn',
                            'buy_rate',
                            'sell_rate',
                        ])
                    )
                    ->etc()
        );
});





it('can select any of the configured fields of each currency', function ($field) {
    Currency::factory()->count(5)->create();

    getJson("/api/currencies?do_not_paginate=1&fields[currencies]={$field}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has('data.currencies', Currency::count())
                    ->has(
                        'data.currencies.0',
                        fn (AssertableJson $json) => $json->has($field)->etc()
                    )
                    ->etc()
        );
})->with([
    'id',
    'name',
    'code',
    'exchange_rate_to_ngn',
    'buy_rate',
    'sell_rate',
]);





it('throws the InvalidFieldQuery when an unconfigured field in the API', function ($field) {
    Currency::factory()->count(5)->create();

    getJson("/api/currencies?do_not_paginate=1&fields[currencies]={$field}")
        ->assertStatus(400);
})->with([
    'created_at',
    'updated_at',
    'deleted_at',
]);





it('filters currencies by code', function ($code) {
    getJson("/api/currencies?do_not_paginate=1&filter[code]={$code}")
        ->assertOk()
        ->assertJsonCount(1, 'data.currencies');
})->with([
    'ng' => fn () => Currency::factory()->create(['name' => 'Nigeria', 'code' => 'NG'])->code,
    'us' => fn () => Currency::factory()->create(['name' => 'USA', 'code' => 'US'])->code,
]);





it('sorts the currencies by a column in a certain order', function ($order, $column) {
    $symbol = $order === 'asc' ? '' : '-';

    Currency::factory()->count(10)->create();

    $response = getJson("/api/currencies?do_not_paginate=1&sort={$symbol}{$column}&fields[currencies]=id,{$column}");

    $response->assertOk();

    $sortedCurrencies = collect(Currency::query()->select(['id', $column])->orderBy($column, $order)->get())
        ->pluck($column)
        ->toArray();

    $responseCurrencies = $response->collect('data.currencies')->pluck($column)->toArray();

    expect($sortedCurrencies === $responseCurrencies)->toBeTrue();
})->with([
    'asc',
    'desc',
])->with([
    'code',
]);

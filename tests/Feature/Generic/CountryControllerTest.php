<?php

use App\Models\Country;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'country');





it('can get the countries', function () {
    getJson('/api/countries')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'countries' => [
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
                    ->where('message', 'Countries fetched successfully')
                    ->whereType('data', 'array')
        );
});





it('includes the query string for paginated response', function () {
    getJson('/api/countries')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.countries.first_page_url', url('api/countries') . '?page=1')
                    ->etc()
        );
});





it('includes the query string with a filter for paginated response', function () {
    $query = http_build_query([
        'fields[countries]' => 'id',
        'page' => 1,
    ]);

    getJson("/api/countries?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.countries.first_page_url', url('api/countries') . "?{$query}")
                    ->etc()
        );
});





it('fetch the all countries from storage without pagination', function () {
    $queryString = '?do_not_paginate=1';

    getJson("/api/countries{$queryString}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'countries',
            ],
        ])
        ->assertJsonCount(Country::count(), 'data.countries');
});





it('selects the id, name, alpha2_code, alpha2_code, dialing_code and flag_url of each country by default', function () {
    Country::factory()->count(5)->create();

    getJson('/api/countries?do_not_paginate=1')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has('data.countries', Country::count())
                    ->has(
                        'data.countries.0',
                        fn (AssertableJson $json) => $json->hasAll([
                            'id',
                            'name',
                            'alpha2_code',
                            'alpha3_code',
                            'dialing_code',
                            'flag_url',
                        ])
                    )
                    ->etc()
        );
});





it('can add the dialing_code to selection for each country', function () {
    Country::factory()->count(5)->create();

    getJson('/api/countries?do_not_paginate=1&fields[countries]=id,dialing_code')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has('data.countries', Country::count())
                    ->has(
                        'data.countries.0',
                        fn (AssertableJson $json) => $json->has('dialing_code')->etc()
                    )
                    ->etc()
        );
});





it('can select any of the configured fields of each country', function ($field) {
    Country::factory()->count(5)->create();

    getJson("/api/countries?do_not_paginate=1&fields[countries]={$field}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has('data.countries', Country::count())
                    ->has(
                        'data.countries.0',
                        fn (AssertableJson $json) => $json->has($field)->etc()
                    )
                    ->etc()
        );
})->with([
    'id',
    'name',
    'alpha2_code',
    'alpha3_code',
    'flag_url',
    'dialing_code',
]);





it('throws the InvalidFieldQuery when an unconfigured field in the API', function ($field) {
    Country::factory()->count(5)->create();

    getJson("/api/countries?do_not_paginate=1&fields[countries]={$field}")
        ->assertStatus(400);
})->with([
    'created_at',
    'updated_at',
    'deleted_at',
]);





it('filters countries by name', function ($name) {
    getJson("/api/countries?do_not_paginate=1&filter[name]={$name}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries');
})->with([
    'nigeria' => fn () => Country::factory()->create(['name' => 'Nigeria'])->name,
    'niger' => fn () => substr(Country::factory()->create(['name' => 'Nigeria'])->name, 0, 5),
]);





it('filters countries by alpha2_code', function ($alpha2_code) {
    getJson("/api/countries?do_not_paginate=1&filter[alpha2_code]={$alpha2_code}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries');
})->with([
    'ng' => fn () => Country::factory()->create(['name' => 'Nigeria', 'alpha2_code' => 'NG'])->alpha2_code,
    'us' => fn () => Country::factory()->create(['name' => 'USA', 'alpha2_code' => 'US'])->alpha2_code,
]);





it('filters countries by alpha3_code', function ($alpha3_code) {
    getJson("/api/countries?do_not_paginate=1&filter[alpha3_code]={$alpha3_code}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries');
})->with([
    'nga' => fn () => Country::factory()->create(['name' => 'Nigeria', 'alpha3_code' => 'NGA'])->alpha3_code,
    'usa' => fn () => Country::factory()->create(['name' => 'USA', 'alpha3_code' => 'USA'])->alpha3_code,
]);





it('filters country by registration activated', function ($registrationActivated) {
    Country::factory()->create(['registration_activated_at' => (bool) $registrationActivated ? now() : null]);

    getJson("/api/countries?do_not_paginate=1&filter[registration_activated]={$registrationActivated}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries');
})->with([
    0,
    1
]);





it('filters country by giftcard activated', function ($giftcardActivated) {
    Country::factory()->create(['giftcard_activated_at' => (bool) $giftcardActivated ? now() : null]);

    getJson("/api/countries?do_not_paginate=1&filter[giftcard_activated]={$giftcardActivated}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries');
})->with([
    0,
    1
]);





it('sorts the countries by a column in a certain order', function ($order, $column) {
    $symbol = $order === 'asc' ? '' : '-';

    Country::factory()->count(10)->create();

    $response = getJson("/api/countries?sort={$symbol}{$column}&fields[countries]=id,{$column}");

    $response->assertOk();

    $sortedCountries = collect(Country::query()->select(['id', $column])->orderBy($column, $order)->paginate()->items())
        ->pluck($column)
        ->toArray();

    $responseCountries = $response->collect('data.countries.data')->pluck($column)->toArray();

    expect($sortedCountries === $responseCountries)->toBeTrue();
})->with([
    'asc',
    'desc',
])->with([
    'name',
    'alpha2_code',
    'alpha3_code',
]);

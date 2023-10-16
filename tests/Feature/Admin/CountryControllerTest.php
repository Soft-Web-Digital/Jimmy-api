<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Country;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;

uses()->group('api', 'country');





it('rejects unpermitted admin from getting countries', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/countries{$path}")
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
    'show' => fn () => [
        'method' => 'GET',
        'path' => '/' . Country::factory()->create()->id,
    ],
    'toggle registration' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Country::factory()->create()->id . '/registration',
    ],
    'toggle giftcard' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Country::factory()->create()->id . '/giftcard',
    ],
]);





it('get countries as an admin', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_COUNTRIES);

    getJson('/api/admin/countries')
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
                    ->has(
                        'data.countries.data',
                        Country::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(Country::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('includes the query string in paginated countries list as an admin', function () {
    Country::factory()->count(10)->create();

    $admin = Admin::factory()->secure()->create(['country_id' => Country::value('id')])->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_COUNTRIES);

    $query = http_build_query([
        'fields[countries]' => 'id,name',
        'per_page' => 5,
        'page' => 1,
    ]);

    getJson("/api/admin/countries?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.countries.first_page_url', url('api/admin/countries') . "?{$query}")
                    ->etc()
        );
});





it('selects only specified fields in countries list', function ($fields) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_COUNTRIES);

    $query = http_build_query([
        'fields[countries]' => $fields,
    ]);

    getJson("/api/admin/countries?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.countries.data',
                    Country::paginate()->count(),
                    fn (AssertableJson $json) =>
                        $json->hasAll(explode(',', $fields))
                )->etc()
        );
})->with([
    'id,name',
    'id,dialing_code,alpha3_code',
    'id,flag_url,alpha2_code',
    'id,created_at,updated_at,deleted_at',
]);





it('filters by country name', function () {
    Country::factory()->count(10)->create();

    $admin = Admin::factory()->secure()->create(['country_id' => Country::value('id')])->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_COUNTRIES);

    $query = http_build_query([
        'filter[name]' => Country::value('name'),
    ]);

    getJson("/api/admin/countries?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries.data');
});





it('filters by country alpha2_code', function () {
    Country::factory()->count(10)->create();

    $admin = Admin::factory()->secure()->create(['country_id' => Country::value('id')])->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_COUNTRIES);

    $query = http_build_query([
        'filter[alpha2_code]' => Country::value('alpha2_code'),
    ]);

    getJson("/api/admin/countries?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries.data');
});





it('filters by country alpha3_code', function () {
    Country::factory()->count(10)->create();

    $admin = Admin::factory()->secure()->create(['country_id' => Country::value('id')])->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_COUNTRIES);

    $query = http_build_query([
        'filter[alpha3_code]' => Country::value('alpha3_code'),
    ]);

    getJson("/api/admin/countries?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries.data');
});





it('filters by country activated for registration', function () {
    Country::factory()->registrationActivated()->count(1)->create();

    $admin = Admin::factory()->secure()->create(['country_id' => Country::value('id')])->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_COUNTRIES);

    $query = http_build_query([
        'filter[registration_activated]' => 1,
    ]);

    getJson("/api/admin/countries?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries.data');
});





it('filters by country activated for giftcard', function () {
    Country::factory()->giftcardActivated()->create();

    $admin = Admin::factory()->secure()->create(['country_id' => Country::value('id')])->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_COUNTRIES);

    $query = http_build_query([
        'filter[giftcard_activated]' => 1,
    ]);

    getJson("/api/admin/countries?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data.countries.data');
});





it('sorts the countries by a column in a certain order', function ($order, $column) {
    $symbol = $order === 'asc' ? '' : '-';

    Country::factory()->count(10)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_COUNTRIES);

    $response = getJson("/api/admin/countries?sort={$symbol}{$column}&fields[countries]=id,{$column}");

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





it('shows a country data', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_COUNTRIES);

    $country = Country::factory()->create()->refresh();

    getJson("/api/admin/countries/{$country->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Country fetched successfully')
                    ->where('data.country', $country)
        );
});





it('toggles country registration status', function ($status) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_COUNTRIES);

    $country = Country::factory()->create(['registration_activated_at' => $status ? now() : null])->refresh();

    patchJson("/api/admin/countries/{$country->id}/registration")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', "Country's user registration usage updated successfully")
                    ->whereNot('data.country.registration_activated_at', $country->registration_activated_at)
                    ->whereType('data.country.registration_activated_at', !$status ? 'string' : 'null')
        );
})->with([
    'ON' => fn () => true,
    'OFF' => fn () => false,
]);





it('toggles country giftcard status', function ($status) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_COUNTRIES);

    $country = Country::factory()->create(['giftcard_activated_at' => $status ? now() : null])->refresh();

    patchJson("/api/admin/countries/{$country->id}/giftcard")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', "Country's giftcard usage updated successfully")
                    ->whereNot('data.country.giftcard_activated_at', $country->giftcard_activated_at)
                    ->whereType('data.country.giftcard_activated_at', !$status ? 'string' : 'null')
        );
})->with([
    'ON' => fn () => true,
    'OFF' => fn () => false,
]);

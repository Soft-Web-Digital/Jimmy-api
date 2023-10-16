<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Currency;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;

uses()->group('api', 'currency');





it('rejects unpermitted admin from accessing currencies', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/currencies{$path}")
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
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Currency::factory()->create()->id,
    ],
]);





it('can update currency\'s exchange rate to ngn', function () {
    $admin = Admin::factory()->secure()->create()->refresh();

    actingAsPermittedAdmin($admin, Permission::MANAGE_CURRENCIES);

    $currency = Currency::factory()->create();

    patchJson("/api/admin/currencies/{$currency->id}", [
        'exchange_rate_to_ngn' => fake()->randomFloat(2),
        'buy_rate' => fake()->randomFloat(2),
        'sell_rate' => fake()->randomFloat(2),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Currency updated successfully')
                    ->has(
                        'data.currency',
                        fn (AssertableJson $json) => $json->hasAll([
                            'id',
                            'name',
                            'code',
                            'exchange_rate_to_ngn',
                            'buy_rate',
                            'sell_rate',
                            'created_at',
                            'updated_at',
                            'deleted_at',
                        ])
                    )
        );
});

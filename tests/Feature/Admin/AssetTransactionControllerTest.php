<?php

use App\Enums\ApiErrorCode;
use App\Enums\AssetTransactionStatus;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\AssetTransaction;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;

uses()->group('api', 'asset-transaction');





it('rejects unpermitted admin from hitting the asset transaction API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/asset-transactions{$path}")
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
    'show' => fn () => [
        'method' => 'GET',
        'path' => '/' . AssetTransaction::factory()->create()->id,
    ],
    'decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . AssetTransaction::factory()->create()->id . '/decline',
    ],
    'approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . AssetTransaction::factory()->create()->id . '/approve',
    ],
]);





it('can get all asset transactions', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSET_TRANSACTIONS);

    AssetTransaction::factory()->create();

    getJson('/api/admin/asset-transactions')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'asset_transactions' => [
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
                    ->where('message', 'Asset transactions fetched successfully')
                    ->has(
                        'data.asset_transactions.data',
                        AssetTransaction::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(collect(AssetTransaction::query()->first()->toArray())->keys()->toArray())
                    )
        );
});





it('can get a single asset transaction', function ($id) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSET_TRANSACTIONS);

    $assetTransaction = AssetTransaction::factory()->create()->refresh();

    getJson("/api/admin/asset-transactions/{$assetTransaction->$id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Asset transaction fetched successfully')
                    ->where('data.asset_transaction.id', $assetTransaction->id)
                    ->etc()
        );
})->with(['id', 'reference']);





it('can decline an asset transaction', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSET_TRANSACTIONS);

    $assetTransaction = AssetTransaction::factory()
        ->create(['status' => AssetTransactionStatus::TRANSFERRED])
        ->refresh();

    patchJson("/api/admin/asset-transactions/{$assetTransaction->id}/decline")
        ->assertOk()
        ->assertJsonFragment([
            'status' => AssetTransactionStatus::DECLINED->value,
        ]);
});





it('can approve an asset transaction', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSET_TRANSACTIONS);

    $assetTransaction = AssetTransaction::factory()
        ->create(['status' => AssetTransactionStatus::TRANSFERRED])
        ->refresh();

    patchJson("/api/admin/asset-transactions/{$assetTransaction->id}/approve", [
        'complete_approval' => true,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'status' => AssetTransactionStatus::APPROVED->value,
        ]);
});



it('can export asset transactions', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ASSET_TRANSACTIONS);

    AssetTransaction::factory()->count(5)->create();

    Excel::fake();

    getJson('/api/admin/asset-transactions/export')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
            $json->where('data.path', asset(Storage::url('exports/assets.xlsx')))
                ->etc()
        );

    Excel::assertStored('exports/assets.xlsx', 'public');
});

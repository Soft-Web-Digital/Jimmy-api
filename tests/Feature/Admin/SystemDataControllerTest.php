<?php

use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\SystemData;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;

uses()->group('api', 'system-data');





it('rejects unpermitted admin from hitting the system data API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/system-data{$path}")
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
        'path' => '/' . SystemData::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . SystemData::factory()->create()->id,
    ],
]);





it('gets all system data with their datatype', function () {
    SystemData::factory()->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_SYSTEM_DATA);

    getJson('/api/admin/system-data')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'System data fetched successfully.')
                    ->has('data.system_data', 1)
        );
});





it('gets a single system data with its datatype', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_SYSTEM_DATA);

    $systemData = SystemData::factory()->create()->refresh();

    getJson("/api/admin/system-data/{$systemData->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'System data fetched successfully.')
                    ->whereType('data.system_data', 'array')
        )
        ->assertJsonStructure([
            'data' => [
                'system_data' => array_merge(collect($systemData->toArray())->keys()->toArray(), [
                    'datatype' => [
                        'id',
                        'name',
                        'hint',
                    ],
                ])
            ]
        ]);
});





it('can update the content of a system data', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_SYSTEM_DATA);

    $systemData = SystemData::factory()->create()->refresh();

    /** @var \App\Enums\SystemDataCode $systemDataEnum */
    $systemDataEnum = $systemData->code;

    patchJson("/api/admin/system-data/{$systemData->id}", [
        'content' => $systemDataEnum->defaultContent(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'System data updated successfully.')
                    ->where('data.system_data.content', $systemDataEnum->defaultContent())
        );
});





it('cannot update the content of a system data with the wrong datatype', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_SYSTEM_DATA);

    $systemData = SystemData::factory()->create()->refresh();

    Storage::fake();

    patchJson("/api/admin/system-data/{$systemData->id}", [
        'content' => UploadedFile::fake()->image('file.jpg'),
    ])
        ->assertJsonValidationErrorFor('content', 'data.errors');
});

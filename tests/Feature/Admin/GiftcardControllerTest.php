<?php

use App\Enums\ApiErrorCode;
use App\Enums\GiftcardCardType;
use App\Enums\GiftcardStatus;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Giftcard;
use App\Models\Role;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;

uses()->group('api', 'giftcard');





it('rejects unpermitted admin from hitting the giftcard API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/giftcards{$path}")
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
        'path' => '/' . Giftcard::factory()->create()->id,
    ],
    'decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Giftcard::factory()->create()->id . '/decline',
    ],
    'approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Giftcard::factory()->create()->id . '/approve',
    ],
]);





it('can get giftcards in a paginated format', function () {
    actingAsSuperAdmin(Admin::factory()->secure()->create()->refresh());

    Giftcard::factory()->create();

    getJson('/api/admin/giftcards')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'giftcards' => [
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
                    ->where('message', 'Giftcards fetched successfully')
                    ->has(
                        'data.giftcards.data',
                        Giftcard::paginate()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll(array_merge(
                                collect(Giftcard::query()->first()->toArray())->keys()->toArray(),
                                ['children_count']
                            ))
                    )
        );
});





it('can get a single giftcard', function ($id) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARDS);

    $giftcard = Giftcard::factory()->create()->refresh();

    getJson("/api/admin/giftcards/{$giftcard->$id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard fetched successfully')
                    ->where('data.giftcard.id', $giftcard->id)
                    ->etc()
        );
})->with(['id', 'reference']);





it('can show the pin and code for a virtual giftcard', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARDS);

    $giftcard = Giftcard::factory()->create(['card_type' => GiftcardCardType::VIRTUAL]);

    getJson("/api/admin/giftcards/{$giftcard->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard fetched successfully')
                    ->where('data.giftcard.id', $giftcard->id)
                    ->whereType('data.giftcard.code', 'string')
                    ->whereType('data.giftcard.pin', 'string')
                    ->whereType('data.giftcard.cards', 'array')
                    ->etc()
        );
});





it('can show the card for a physical giftcard', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARDS);

    Storage::fake();

    $giftcard = Giftcard::factory()->create(['card_type' => GiftcardCardType::PHYSICAL]);
    $giftcard->addMedia(UploadedFile::fake()->image('card.jpg'));

    getJson("/api/admin/giftcards/{$giftcard->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard fetched successfully')
                    ->where('data.giftcard.id', $giftcard->id)
                    ->whereType('data.giftcard.code', 'null')
                    ->whereType('data.giftcard.pin', 'null')
                    ->whereType('data.giftcard.cards', 'array')
                    ->etc()
        );
});





it('can decline a giftcard trade', function ($admin) {
    /** @var \App\Models\Admin $admin */

    actingAsPermittedAdmin($admin, Permission::MANAGE_GIFTCARDS);

    $giftcard = Giftcard::factory()->create(['card_type' => GiftcardCardType::VIRTUAL])->refresh();

    if (!$admin->hasRole('SUPERADMIN')) {
        $admin->giftcardCategories()->sync($giftcard->giftcardProduct()->value('giftcard_category_id'));
    }

    patchJson("/api/admin/giftcards/{$giftcard->id}/decline")
        ->assertOk()
        ->assertJsonFragment([
            'status' => GiftcardStatus::DECLINED->value,
        ]);
})->with([
    'superadmin' => fn () => Admin::factory()
        ->secure()
        ->has(Role::factory()->guard('api_admin')->state(fn ($attributes) => ['name' => 'SUPERADMIN']))
        ->create(),
    'regular admin' => fn () => Admin::factory()->secure()->create(),
]);





it('can approve a giftcard trade', function ($admin) {
    /** @var \App\Models\Admin $admin */

    actingAsPermittedAdmin($admin, Permission::MANAGE_GIFTCARDS);

    $giftcard = Giftcard::factory()->create(['card_type' => GiftcardCardType::PHYSICAL])->refresh();

    if (!$admin->hasRole('SUPERADMIN')) {
        $admin->giftcardCategories()->sync($giftcard->giftcardProduct()->value('giftcard_category_id'));
    }

    patchJson("/api/admin/giftcards/{$giftcard->id}/approve", [
        'complete_approval' => true,
    ])
        ->assertOk()
        ->assertJsonFragment([
            'status' => GiftcardStatus::APPROVED->value,
        ]);
})->with([
    'superadmin' => fn () => Admin::factory()
        ->secure()
        ->has(Role::factory()->guard('api_admin')->state(fn ($attributes) => ['name' => 'SUPERADMIN']))
        ->create(),
    'regular admin' => fn () => Admin::factory()->secure()->create(),
]);





it('rejects unassigned admin to decline a giftcard trade', function () {
    /** @var \App\Models\Admin $admin */
    $admin = Admin::factory()->secure()->create()->refresh();
    actingAsPermittedAdmin($admin, Permission::MANAGE_GIFTCARDS);

    $giftcard = Giftcard::factory()->create(['card_type' => GiftcardCardType::VIRTUAL])->refresh();

    patchJson("/api/admin/giftcards/{$giftcard->id}/decline")
        ->assertStatus(Response::HTTP_FORBIDDEN);
});





it('rejects unassigned admin to approve a giftcard trade', function () {
    /** @var \App\Models\Admin $admin */
    $admin = Admin::factory()->secure()->create()->refresh();
    actingAsPermittedAdmin($admin, Permission::MANAGE_GIFTCARDS);

    $giftcard = Giftcard::factory()->create(['card_type' => GiftcardCardType::PHYSICAL])->refresh();

    patchJson("/api/admin/giftcards/{$giftcard->id}/approve", [
        'complete_approval' => true,
    ])
        ->assertStatus(Response::HTTP_FORBIDDEN);
});



it('can export giftcard transactions', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_GIFTCARDS);

    Giftcard::factory()->count(5)->create();

    Excel::fake();

    getJson('/api/admin/giftcards/export')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
            $json->where('data.path', asset(Storage::url('exports/giftcards.xlsx')))
                ->etc()
        );

    Excel::assertStored('exports/giftcards.xlsx', 'public');
});

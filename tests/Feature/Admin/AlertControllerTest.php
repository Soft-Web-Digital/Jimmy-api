<?php

use App\Enums\AlertChannel;
use App\Enums\AlertStatus;
use App\Enums\AlertTargetUser;
use App\Enums\ApiErrorCode;
use App\Enums\Permission;
use App\Models\Admin;
use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\json;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'alert');





it('rejects unpermitted admin from hitting the alerts API', function ($route) {
    $admin = Admin::factory()->secure()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin/alerts{$path}")
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
    'store' => fn () => [
        'method' => 'POST',
        'path' => '',
    ],
    'show' => fn () => [
        'method' => 'GET',
        'path' => '/' . Alert::factory()->create()->id,
    ],
    'update' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Alert::factory()->create()->id,
    ],
    'destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/' . Alert::factory()->create()->id,
    ],
    'restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/' . Alert::factory()->create()->id . '/restore',
    ],
    'dispatch' => fn () => [
        'method' => 'POST',
        'path' => '/' . Alert::factory()->create()->id . '/dispatch',
    ],
]);





it('get alerts as an admin', function () {
    Alert::factory()->count(3)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    getJson('/api/admin/alerts')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'alerts' => [
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
                    ->where('message', 'Alerts fetched successfully')
                    ->has(
                        'data.alerts.data',
                        Alert::query()->count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'title',
                                'status',
                                'target_user',
                                'target_user_count',
                                'dispatched_at',
                                'creator_id',
                                'created_at',
                                'updated_at',
                            ])
                    )
        );
});





it('includes the query string in paginated alerts list', function () {
    Alert::factory()->count(10)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $query = http_build_query([
        'fields[alerts]' => 'id,title',
        'per_page' => 5,
        'page' => 1,
    ]);

    getJson("/api/admin/alerts?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.alerts.first_page_url', url('api/admin/alerts') . "?{$query}")
                    ->etc()
        );
});





it('selects only specified fields in alerts list', function ($fields) {
    Alert::factory()->count(10)->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $query = http_build_query([
        'fields[alerts]' => $fields,
    ]);

    getJson("/api/admin/alerts?{$query}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->has(
                    'data.alerts.data',
                    Alert::paginate()->count(),
                    fn (AssertableJson $json) =>
                        $json->hasAll(explode(',', $fields))
                )->etc()
        );
})->with([
    'id,title',
    'id,status,target_user,target_user_count',
    'id,dispatched_at,creator_id',
    'id,created_at,updated_at,deleted_at',
]);





it('filters the alert list by certain conditions', function ($data) {
    ['column' => $column, 'value' => $value] = $data;

    $countQuery = match ($column) {
        'only_trashed' => Alert::withTrashed()->whereNotNull('deleted_at'),
        'with_trashed' => Alert::withTrashed(),
        'status' => Alert::query()->where('status', $value),
        'dispatch_date' => Alert::query()
            ->where('dispatched_at', '>=', explode(',', $value)[0])
            ->where('dispatched_at', '<=', explode(',', $value)[1]),
    };

    $sequence = match ($column) {
        'only_trashed', 'with_trashed' => new Sequence(
            ['deleted_at' => now()],
            ['deleted_at' => null],
        ),
        'status' => new Sequence(
            ['status' => $value],
            ['status' => collect(AlertStatus::values())->random()],
        ),
        'dispatch_date' => new Sequence(
            ['dispatched_at' => explode(',', $value)[0]],
            ['dispatched_at' => explode(',', $value)[1]],
        ),
        default => new Sequence([]),
    };

    if (str_contains($column, 'trashed')) {
        $column = 'trashed';
    }

    Alert::factory()->count(10)->state($sequence)->create();

    actingAsPermittedAdmin(Admin::factory()->create(), Permission::MANAGE_ALERTS);

    $query = http_build_query([
        "filter[{$column}]" => $value,
    ]);

    getJson("/api/admin/alerts?{$query}")
        ->assertOk()
        ->assertJsonCount($countQuery->count(), 'data.alerts.data');
})->with([
    'only_trashed' => fn () => [
        'column' => 'only_trashed',
        'value' => 'only',
    ],
    'with_trashed' => fn () => [
        'column' => 'with_trashed',
        'value' => 'with',
    ],
    'status' => fn () => [
        'column' => 'status',
        'value' => collect(AlertStatus::values())->random(),
    ],
    'dispatch_date' => fn () => [
        'column' => 'dispatch_date',
        'value' => Alert::factory()->create()->dispatched_at->toDateTimeString()
            . ','
            . Alert::factory()->create()->dispatched_at->toDateTimeString(),
    ],
]);





it('sorts the countries by a column in a certain order', function ($order, $column) {
    $symbol = $order === 'asc' ? '' : '-';

    Alert::factory()
        ->state(new Sequence(fn ($sequence) => ['status' => collect(AlertStatus::values())->random()]))
        ->count(10)
        ->create();

    actingAsPermittedAdmin(Admin::factory()->secure()->create(), Permission::MANAGE_ALERTS);

    $response = getJson("/api/admin/alerts?sort={$symbol}{$column}&fields[alerts]=id,{$column}");

    $response->assertOk();

    $sortedAlerts = collect(Alert::query()->select(['id', $column])->orderBy($column, $order)->paginate()->items())
        ->pluck($column)
        ->map(function ($item) {
            if ($item instanceof \BackedEnum) {
                return $item->value;
            }

            if ($item instanceof \Illuminate\Support\Carbon) {
                return now()->parse($item)->toISOString();
            }

            return $item;
        })
        ->toArray();

    $responseAlerts = $response->collect('data.alerts.data')->pluck($column)->toArray();

    expect($sortedAlerts === $responseAlerts)->toBeTrue();
})->with([
    'asc',
    'desc',
])->with([
    'status',
    'target_user',
    'target_user_count',
    'dispatched_at',
]);





it('verifies a valid title to create alerts', function ($title) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $message = match ($title) {
        null => trans('validation.required', ['attribute' => 'title']),
        1 => trans('validation.string', ['attribute' => 'title']),
        default => trans('validation.max.string', ['attribute' => 'title', 'max' => '255']),
    };

    postJson('/api/admin/alerts', [
        'title' => $title,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('title', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.title.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'not string' => 1,
    'string too long' => str_repeat('a', 256),
]);





it('verifies a valid body to create alerts', function ($body) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $message = match ($body) {
        null => trans('validation.required', ['attribute' => 'body']),
        default => trans('validation.string', ['attribute' => 'body']),
    };

    postJson('/api/admin/alerts', [
        'body' => $body,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('body', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.body.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'not string' => 1,
]);





it('verifies a valid target_user to create alerts', function ($target_user) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $message = match ($target_user) {
        null => trans('validation.required', ['attribute' => 'target user']),
        default => trans('validation.enum', ['attribute' => 'target user']),
    };

    postJson('/api/admin/alerts', [
        'target_user' => $target_user,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('target_user', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.target_user.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'not a valid alert status' => fn () => fake()->word(),
]);





it('verifies a valid dispatch_datetime to create alerts', function ($dispatch_datetime) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $message = match ($dispatch_datetime) {
        null => trans('validation.required', ['attribute' => 'dispatch datetime']),
        'string' => trans('validation.date', ['attribute' => 'dispatch datetime']),
        default => trans('validation.after_or_equal', [
            'attribute' => 'dispatch datetime',
            'date' => 'now + 5 minutes'
        ]),
    };

    postJson('/api/admin/alerts', [
        'dispatch_datetime' => $dispatch_datetime,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('dispatch_datetime', 'data.errors')
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('data.errors.dispatch_datetime.0', $message)
                    ->etc()
        );
})->with([
    'empty value' => null,
    'not a valid date' => 'string',
    'past date' => fn () => now()->subHour()->toDateTimeString(),
]);





it('verifies at least one valid channel to create alerts', function ($channels) {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    postJson('/api/admin/alerts', [
        'channels' => $channels,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('channels' . (!in_array($channels, [null, 1]) ? '.0' : ''), 'data.errors');
})->with([
    'empty value' => null,
    'not an array' => fn () => [],
    'invalid channel' => fn () => ['wrong channel'],
]);





it('can create alerts', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    postJson('/api/admin/alerts', [
        'title' => fake()->sentence(),
        'body' => fake()->sentence(),
        'target_user' => AlertTargetUser::ALL->value,
        'dispatch_datetime' => now()->addDay(),
        'channels' => array_unique([
            AlertChannel::random()->value,
            AlertChannel::random()->value,
        ]),
    ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Alert created successfully')
                    ->where('data.alert', Alert::latest()->first())
        );
});





it('can show a single alert', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $alert = Alert::factory()->create()->refresh();

    getJson("/api/admin/alerts/{$alert->id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Alert fetched successfully')
                    ->where('data.alert', $alert->toArray())
        );
});





it('can update the title of an alert', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $alert = Alert::factory()->create();

    patchJson("/api/admin/alerts/{$alert->id}", [
        'title' => fake()->sentence(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Alert updated successfully')
                    ->where('data.alert', Alert::latest()->first())
        );
});





it('can update the body of an alert', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $alert = Alert::factory()->create();

    patchJson("/api/admin/alerts/{$alert->id}", [
        'body' => fake()->sentence(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Alert updated successfully')
                    ->where('data.alert', Alert::latest()->first())
        );
});





it('can update the target_user of an alert', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $alert = Alert::factory()->create();

    patchJson("/api/admin/alerts/{$alert->id}", [
        'target_user' => AlertTargetUser::ALL->value,
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Alert updated successfully')
                    ->where('data.alert', Alert::latest()->first())
        );
});





it('can delete alerts', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $alert = Alert::factory()->create();

    deleteJson("/api/admin/alerts/{$alert->id}")->assertNoContent();

    expect(Alert::find($alert->id))->toBeNull();
});





it('can restore alerts', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $alert = Alert::factory()->deleted()->create();

    patchJson("/api/admin/alerts/{$alert->id}/restore")->assertOk();

    expect(Alert::find($alert->id))->not->toBeNull();
});





it('can dispatch alerts', function () {
    actingAsPermittedAdmin(Admin::factory()->secure()->create()->refresh(), Permission::MANAGE_ALERTS);

    $alert = Alert::factory()->create();

    postJson("/api/admin/alerts/{$alert->id}/dispatch")
        ->assertOk()
        ->assertJsonFragment([
            'status' => config('queue.default') === 'sync'
                ? AlertStatus::SUCCESSFUL->value
                : AlertStatus::ONGOING->value
        ]);
});

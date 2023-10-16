<?php

use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'notifications');





it('can fetch all notifications', function () {
    $user = User::factory()->create()->refresh();

    $user->notifyNow(new VerifyEmailNotification('some code'), ['database']);

    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/notifications')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'notifications' => [
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
                    ->where('message', 'Notifications fetched successfully.')
                    ->has(
                        'data.notifications.data',
                        1,
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'type',
                                'notifiable_id',
                                'notifiable_type',
                                'data.title',
                                'data.body',
                                'read_at',
                                'created_at',
                                'updated_at',
                            ])
                    )
        );
});





it('can filter notifications based on read status', function ($status) {
    $user = User::factory()->create()->refresh();

    $user->notifyNow(new VerifyEmailNotification('some code'), ['database']);

    if ((bool) $status) {
        $user->notifications()->update(['read_at' => now()]);
    }

    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/notifications?filter[read]=' . (int) $status)
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Notifications fetched successfully.')
                    ->has(
                        'data.notifications.data',
                        1,
                        fn (AssertableJson $json) => $json->whereType('read_at', $status ? 'string' : 'null')
                            ->etc()
                    )
        );
})->with([
    true,
    false
]);





it('sorts the notifications by a column in a certain order', function ($order, $column) {
    $symbol = $order === 'asc' ? '' : '-';

    $user = User::factory()->create()->refresh();

    $user->notifyNow(new VerifyEmailNotification('some code'), ['database']);

    sanctumLogin($user, ['*'], 'api_user');

    $response = getJson("/api/user/notifications?sort={$symbol}{$column}");

    $response->assertOk();

    $sortedNotifications = collect(DatabaseNotification::query()->orderBy($column, $order)->paginate()->items())
        ->pluck($column)
        ->map(function ($item) {
            if ($item instanceof \Illuminate\Support\Carbon) {
                return now()->parse($item)->toISOString();
            }

            return $item;
        })
        ->toArray();

    $responseNotifications = $response->collect('data.notifications.data')->pluck($column)->toArray();

    expect($sortedNotifications === $responseNotifications)->toBeTrue();
})->with([
    'asc',
    'desc',
])->with([
    'read_at',
    'created_at',
]);





it('can read specified notifications', function () {
    $user = User::factory()->create()->refresh();

    $user->notifyNow(new VerifyEmailNotification('some code'), ['database']);

    sanctumLogin($user, ['*'], 'api_user');

    postJson('/api/user/notifications/read', [
        'notifications' => $user->notifications()->pluck('id')->toArray(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Notification(s) marked as read')
                    ->where('data', null)
        );
});

<?php

use App\Enums\AlertChannel;
use App\Enums\AlertTargetUser;
use App\Mail\AlertDispatched;
use App\Models\Alert;
use App\Models\User;
use App\Services\Alert\AlertDispatchService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;

uses()->group('service', 'alert');





it('can dispatch alert via mail', function ($targetUser) {
    Mail::fake();

    $alert = Alert::factory()->create([
        'target_user' => $targetUser,
        'channels' => [AlertChannel::EMAIL->value]
    ]);

    (new AlertDispatchService())->dispatch($alert);

    Mail::assertQueued(AlertDispatched::class);
})->with(
    AlertTargetUser::values(),
);





it('can dispatch alert via in_app', function ($targetUser) {
    $alert = Alert::factory()->create([
        'target_user' => $targetUser,
        'channels' => [AlertChannel::IN_APP->value]
    ]);

    switch ($targetUser) {
        case AlertTargetUser::ALL->value:
            User::factory()->create();
            break;

        case AlertTargetUser::VERIFIED->value:
            User::factory()->verified()->create();
            break;
    }

    (new AlertDispatchService())->dispatch($alert);

    test()->assertDatabaseCount((new DatabaseNotification())->getTable(), 2);
})->with(
    AlertTargetUser::values(),
);





it('can dispatch alert via push', function ($targetUser) {
    $alert = Alert::factory()->create([
        'target_user' => $targetUser,
        'channels' => [AlertChannel::PUSH->value]
    ]);

    switch ($targetUser) {
        case AlertTargetUser::ALL->value:
            User::factory()->create();
            break;

        case AlertTargetUser::VERIFIED->value:
            User::factory()->verified()->create();
            break;
    }

    Mail::fake();

    (new AlertDispatchService())->dispatch($alert);

    test()->assertDatabaseCount((new DatabaseNotification())->getTable(), 0);

    Mail::assertNothingQueued();
})->with(
    AlertTargetUser::values(),
);





it('can dispatch alert via both mail and in_app', function ($targetUser) {
    Mail::fake();

    $alert = Alert::factory()->create([
        'target_user' => $targetUser,
        'channels' => [
            AlertChannel::EMAIL->value,
            AlertChannel::IN_APP->value,
        ]
    ]);

    switch ($targetUser) {
        case AlertTargetUser::ALL->value:
            User::factory()->create();
            break;

        case AlertTargetUser::VERIFIED->value:
            User::factory()->verified()->create();
            break;
    }

    (new AlertDispatchService())->dispatch($alert);

    Mail::assertQueued(AlertDispatched::class);

    test()->assertDatabaseCount((new DatabaseNotification())->getTable(), 2);
})->with(
    AlertTargetUser::values(),
);

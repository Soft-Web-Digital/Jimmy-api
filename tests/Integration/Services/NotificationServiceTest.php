<?php

use App\Notifications\Auth\VerifyEmailNotification;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;

uses()->group('service', 'profile', 'notifications');





it('can read a single notification', function ($user) {
    /** @var \App\Models\User|\App\Models\Admin $user */

    $user->notifyNow(new VerifyEmailNotification('some code'), ['database']);

    $notification = DatabaseNotification::query()->whereMorphedTo('notifiable', $user)->first();

    (new NotificationService())->markAsRead($user, $notification->id);

    $notification->refresh();

    expect((bool) $notification->read_at)->toBeTrue();
})->with('authenticable_models');





it('can read multiple notification', function ($user) {
    /** @var \App\Models\User|\App\Models\Admin $user */

    $user->notifyNow(new VerifyEmailNotification('some code'), ['database']);
    $user->notifyNow(new VerifyEmailNotification('some other code'), ['database']);

    $notifications = DatabaseNotification::query()->whereMorphedTo('notifiable', $user)->get();

    (new NotificationService())->markAsRead($user, $notifications->pluck('id')->toArray());

    $notifications->map->refresh();

    expect($notifications)
        ->toHaveCount(2)
        ->each(
            fn ($notification) => $notification->read_at->toBeTruthy()
        );
})->with('authenticable_models');

<?php

use App\DataTransferObjects\Models\AlertModelData;
use App\Enums\AlertChannel;
use App\Enums\AlertStatus;
use App\Enums\AlertTargetUser;
use App\Exceptions\ExpectationFailedException;
use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\Alert;
use App\Models\User;
use App\Services\Alert\AlertService;

uses()->group('service', 'alert');





it('can create an alert', function ($targetUser, $channels) {
    login(Admin::factory()->create());

    $alertModelData = (new AlertModelData())
        ->setTitle(fake()->sentence())
        ->setBody(fake()->sentence())
        ->setTargetUser($targetUser)
        ->setDispatchDatetime(fake()->dateTimeInInterval('now'))
        ->setChannels($channels);

    if ($targetUser === AlertTargetUser::SPECIFIC->value) {
        $alertModelData->setUsers([
            User::factory()->create()->id,
        ]);
    }

    expect((new AlertService())->create($alertModelData))->toBeInstanceOf(Alert::class);

    test()->assertDatabaseCount((new Alert())->getTable(), 1);
})->with(
    array_flip(AlertTargetUser::valuePair()),
)->with([
    ...AlertChannel::valuePair(),
    ...[
        'all' => AlertChannel::values(),
    ]
]);





it('requires an authenticated admin to create', function () {
    login(User::factory()->create());

    $alertModelData = (new AlertModelData())
        ->setTitle(fake()->sentence())
        ->setBody(fake()->sentence())
        ->setTargetUser(AlertTargetUser::ALL)
        ->setDispatchDatetime(fake()->dateTimeInInterval('now'))
        ->setChannels(AlertChannel::IN_APP->value);

    (new AlertService())->create($alertModelData);
})->throws(NotAllowedException::class, 'Only admins can create alerts');





it('rejects if no user is provided in AlertModelData for "specific" target user', function () {
    login(Admin::factory()->create());

    $alertModelData = (new AlertModelData())
        ->setTitle(fake()->sentence())
        ->setBody(fake()->sentence())
        ->setTargetUser(AlertTargetUser::SPECIFIC)
        ->setDispatchDatetime(fake()->dateTimeInInterval('now'))
        ->setChannels(AlertChannel::IN_APP->value);

    (new AlertService())->create($alertModelData);
})->throws(ExpectationFailedException::class, 'Provide at least one user to alert');





it('syncs users for creating "specific" alert', function () {
    login(Admin::factory()->create());

    $alertModelData = (new AlertModelData())
        ->setTitle(fake()->sentence())
        ->setBody(fake()->sentence())
        ->setTargetUser(AlertTargetUser::SPECIFIC)
        ->setDispatchDatetime(fake()->dateTimeInInterval('now'))
        ->setChannels(AlertChannel::IN_APP->value)
        ->setUsers([
            User::factory()->create()->id
        ]);

    (new AlertService())->create($alertModelData);

    expect(Alert::latest()->with('users')->first())
        ->users->isNotEmpty()->toBeTrue();
});





it('can update an alert', function ($targetUser, $channels) {
    login(Admin::factory()->create());

    $alert = Alert::factory()->create()->refresh();

    $alertModelData = (new AlertModelData())
        ->setTargetUser($targetUser)
        ->setChannels($channels);

    expect((new AlertService())->update($alert, $alertModelData))
        ->toBeInstanceOf(Alert::class)
        ->target_user->toBe($alertModelData->getTargetUser())
        ->channels->toBe($alertModelData->getChannels());
})->with(
    array_flip(AlertTargetUser::valuePair()),
)->with([
    ...AlertChannel::valuePair(),
    ...[
        'all' => AlertChannel::values(),
    ]
]);





it('detaches all users in specific target_user alert if target_user changes', function () {
    login(Admin::factory()->create());

    $alert = Alert::factory()->create(['target_user' => AlertTargetUser::SPECIFIC->value])->refresh();

    expect($alert->users()->exists())->toBeTrue();

    (new AlertService())->update($alert, (new AlertModelData())->setTargetUser(AlertTargetUser::ALL));

    expect($alert->users()->exists())->toBeFalse();
});





it('throws a `NotAllowedException` if the alert is not pending during update', function ($status) {
    login(Admin::factory()->create());

    $alert = Alert::factory()->create(['status' => $status])->refresh();

    $alertModelData = (new AlertModelData())->setTitle(fake()->sentence());

    (new AlertService())->update($alert, $alertModelData);
})->with(
    array_filter(AlertStatus::values(), fn ($alertStatus) => $alertStatus !== AlertStatus::PENDING->value)
)->throws(NotAllowedException::class);





it('syncs new users for updating "specific" alert', function () {
    login(Admin::factory()->create());

    $alert = Alert::factory()->create(['target_user' => AlertTargetUser::SPECIFIC])->refresh();

    $oldUsers = $alert->users()->pluck('id');

    $alertModelData = (new AlertModelData())
        ->setUsers([
            User::factory()->create()->id
        ]);

    $alert = (new AlertService())->update($alert, $alertModelData)->load('users');

    $newUsers = $alert->users()->pluck('id');

    expect($oldUsers->diff($newUsers)->isEmpty())->toBeFalse();
});





it('retains old users for updating "specific" alert', function () {
    login(Admin::factory()->create());

    $alert = Alert::factory()->create(['target_user' => AlertTargetUser::SPECIFIC])->refresh();

    $oldUsers = $alert->users()->pluck('id');

    $alertModelData = (new AlertModelData())->setTitle(fake()->sentence());

    $alert = (new AlertService())->update($alert, $alertModelData)->load('users');

    $newUsers = $alert->users()->pluck('id');

    expect($oldUsers->diff($newUsers)->isEmpty())->toBeTrue();
});

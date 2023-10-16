<?php

use App\Enums\AlertStatus;
use App\Enums\AlertTargetUser;
use App\Enums\Queue as EnumsQueue;
use App\Jobs\AlertDispatcher;
use App\Models\Alert;
use Illuminate\Support\Facades\Queue;

uses()->group('model', 'alert');





it('casts to the correct types', function () {
    Alert::factory()->create();

    expect(Alert::latest()->first())
        ->title->toBeString()
        ->body->toBeString()
        ->target_user->toBeInstanceOf(AlertTargetUser::class)
        ->status->toBeInstanceOf(AlertStatus::class)
        ->channels->toBeArray();
});





it('marks status as ongoing', function () {
    $alert = Alert::factory()->create();

    Queue::fake();

    $alert->markAsOngoing();

    Queue::assertPushedOn(EnumsQueue::CRITICAL->value, AlertDispatcher::class);

    $alert->refresh();

    expect($alert)
        ->status->toBe(AlertStatus::ONGOING);
});





it('marks status as successful', function () {
    $alert = Alert::factory()->create();

    $alert->markAsSuccessful(1);

    $alert->refresh();

    expect($alert)
        ->status->toBe(AlertStatus::SUCCESSFUL)
        ->target_user_count->toBe(1)
        ->failed_note->toBeNull();
});





it('marks status as failed', function () {
    $alert = Alert::factory()->create();

    $reason = fake()->sentence();

    $alert->markAsFailed($reason);

    $alert->refresh();

    expect($alert)
        ->status->toBe(AlertStatus::FAILED)
        ->failed_note->toBe($reason);
});





test('successful alert does not have a failed note', function () {
    $alert = Alert::factory()->create();

    $alert->markAsFailed(fake()->sentence());

    $alert->markAsSuccessful(1);

    $alert->refresh();

    expect($alert)
        ->failed_note->toBeNull();
});





it('has a morph class of alert', function () {
    expect((new Alert())->getMorphClass())
        ->toBe(strtolower((new ReflectionClass((new Alert())))->getShortName()));
});

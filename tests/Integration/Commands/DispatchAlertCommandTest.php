<?php

use App\Console\Commands\DispatchAlertCommand;
use App\Enums\Queue as EnumsQueue;
use App\Jobs\AlertDispatcher;
use App\Models\Alert;
use Illuminate\Support\Facades\Queue;

uses()->group('command', 'alert');





it('exits successfully', function () {
    test()->artisan(DispatchAlertCommand::class)->assertSuccessful();
});





it('does not dispatch any events when there are no alerts to dispatch', function () {
    Alert::factory()->create();

    Queue::fake();

    test()->artisan(DispatchAlertCommand::class);

    Queue::assertNothingPushed();
});





it('fires the AlertDispatcher job even when there is at least one alert to dispatch', function () {
    Alert::factory()->create([
        'dispatched_at' => now()->subDay(),
    ]);

    Queue::fake();

    test()->artisan(DispatchAlertCommand::class);

    Queue::assertPushedOn(EnumsQueue::CRITICAL->value, AlertDispatcher::class);
});





it('fires the AlertDispatcher job at travel time', function () {
    Alert::factory()->create([
        'dispatched_at' => now()->addDay(),
    ]);

    Queue::fake();

    test()->travelTo(now()->addDay());

    test()->artisan(DispatchAlertCommand::class);

    Queue::assertPushedOn(EnumsQueue::CRITICAL->value, AlertDispatcher::class);
});

<?php

namespace App\Console\Commands;

use App\Enums\AlertStatus;
use App\Enums\Queue;
use App\Jobs\AlertDispatcher;
use App\Models\Alert;
use Illuminate\Console\Command;

class DispatchAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending alerts.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Alert::query()
            ->where('status', AlertStatus::PENDING->value)
            ->where('dispatched_at', '<=', now())
            ->orderBy('id')
            ->chunk(50, function ($alerts) {
                /** @var \App\Models\Alert $alert */
                foreach ($alerts as $alert) {
                    dispatch(new AlertDispatcher($alert))->onQueue(Queue::CRITICAL->value);
                }
            });

        return Command::SUCCESS;
    }
}

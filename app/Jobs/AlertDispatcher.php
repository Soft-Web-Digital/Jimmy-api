<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Alert;
use App\Services\Alert\AlertDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AlertDispatcher implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Alert $alert
     * @return void
     */
    public function __construct(public Alert $alert)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @param \App\Services\Alert\AlertDispatchService $alertDispatchService
     * @return void
     */
    public function handle(AlertDispatchService $alertDispatchService): void
    {
        $alertDispatchService->dispatch($this->alert);
    }
}

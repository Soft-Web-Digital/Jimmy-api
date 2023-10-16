<?php

namespace App\Console;

use App\Console\Commands\CreditRefereeCommand;
use App\Console\Commands\DispatchAlertCommand;
use App\Console\Commands\UpdateBanksCommand;
use App\Console\Commands\UpdateCountriesCommand;
use App\Console\Commands\UpdateCurrenciesCommand;
use App\Console\Commands\UpdateServicedGiftcardCategoriesCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(PruneCommand::class)->daily()->runInBackground();

        $schedule->command(UpdateCountriesCommand::class)->quarterly()->runInBackground();

        $schedule->command(UpdateCurrenciesCommand::class)->quarterly()->runInBackground();

        $schedule->command(UpdateBanksCommand::class)->quarterly()->runInBackground();

        $schedule->command(DispatchAlertCommand::class)->everyFiveMinutes();

        $schedule->command(CreditRefereeCommand::class)->everyFiveMinutes();

        $schedule->command(UpdateServicedGiftcardCategoriesCommand::class)->quarterly()->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

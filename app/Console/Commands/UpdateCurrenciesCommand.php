<?php

namespace App\Console\Commands;

use App\Contracts\Fillers\CurrencyFiller;
use Illuminate\Console\Command;

class UpdateCurrenciesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ksb_currencies:update-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currency list in storage';

    /**
     * Execute the console command.
     *
     * @param \App\Contracts\Fillers\CurrencyFiller $currencyFiller
     * @return int
     */
    public function handle(CurrencyFiller $currencyFiller)
    {
        $this->components->task('Currencies list updating', fn () => $currencyFiller->fillCurrenciesInStorage());

        return Command::SUCCESS;
    }
}

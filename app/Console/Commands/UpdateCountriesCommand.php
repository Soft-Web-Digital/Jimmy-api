<?php

namespace App\Console\Commands;

use App\Contracts\Fillers\CountryFiller;
use Illuminate\Console\Command;

class UpdateCountriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ksb_countries:update-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update country list in storage';

    /**
     * Execute the console command.
     *
     * @param \App\Contracts\Fillers\CountryFiller $countryFiller
     * @return int
     */
    public function handle(CountryFiller $countryFiller)
    {
        $this->components->task('Countries list updating', fn () => $countryFiller->fillCountriesInStorage());

        return Command::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use App\Contracts\Fillers\CurrencyFiller;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param \App\Contracts\Fillers\CurrencyFiller $currencyFiller
     * @return void
     */
    public function run(CurrencyFiller $currencyFiller)
    {
        $currencyFiller->fillCurrenciesInStorage();
    }
}

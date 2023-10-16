<?php

namespace Database\Seeders;

use App\Contracts\Fillers\CountryFiller;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param \App\Contracts\Fillers\CountryFiller $countryFiller
     * @return void
     */
    public function run(CountryFiller $countryFiller)
    {
        $countryFiller->fillCountriesInStorage();
    }
}

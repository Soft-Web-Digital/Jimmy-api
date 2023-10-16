<?php

namespace Database\Seeders;

use App\Contracts\Fillers\BankFiller;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @param \App\Contracts\Fillers\BankFiller $bankFiller
     * @return void
     */
    public function run(BankFiller $bankFiller)
    {
        $bankFiller->fillBanksInStorage();
    }
}

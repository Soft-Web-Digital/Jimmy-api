<?php

namespace App\Contracts\Fillers;

interface CurrencyFiller
{
    /**
     * Fill the database with currencies.
     *
     * @return void
     */
    public function fillCurrenciesInStorage(): void;
}

<?php

namespace App\Contracts\Fillers;

interface BankFiller
{
    public const SUPPORTEDCOUNTRIES = [];

    /**
     * Fill the database with banks.
     *
     * @return void
     */
    public function fillBanksInStorage(): void;
}

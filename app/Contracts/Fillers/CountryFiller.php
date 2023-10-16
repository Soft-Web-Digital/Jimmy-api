<?php

namespace App\Contracts\Fillers;

interface CountryFiller
{
    /**
     * Fill the database with countries.
     *
     * @return void
     */
    public function fillCountriesInStorage(): void;
}

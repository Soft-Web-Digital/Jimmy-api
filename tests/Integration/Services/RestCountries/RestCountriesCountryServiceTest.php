<?php

use App\Contracts\Fillers\CountryFiller;
use App\Services\RestCountries\RestCountriesCountryService;

uses()->group('service', 'external');





it('implements the CountryFiller contract', function () {
    $service = new RestCountriesCountryService();

    expect($service)->toBeInstanceOf(CountryFiller::class);
});

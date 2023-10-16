<?php

use App\Contracts\Fillers\CountryFiller;
use App\Models\Country;
use Illuminate\Support\Facades\App;

uses()->group('contract', 'country', 'external');





it('fills the countries in storage', function () {
    /** @var \App\Contracts\Fillers\CountryFiller $service */
    $service = App::make(CountryFiller::class);

    $service->fillCountriesInStorage();

    expect(Country::all())
        ->toBeCollection()
        ->not->toBeEmpty();
});

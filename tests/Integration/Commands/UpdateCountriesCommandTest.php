<?php

use App\Console\Commands\UpdateCountriesCommand;
use App\Models\Country;

uses()->group('command', 'country', 'external');





it('updates countries list successfully', function () {
    test()->artisan(UpdateCountriesCommand::class)->assertSuccessful();

    expect(Country::all())
        ->toBeCollection()
        ->not->toBeEmpty();
});

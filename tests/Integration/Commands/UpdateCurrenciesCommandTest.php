<?php

use App\Console\Commands\UpdateCurrenciesCommand;
use App\Models\Currency;

uses()->group('command', 'currency', 'external');





it('updates currencies list successfully', function () {
    test()->artisan(UpdateCurrenciesCommand::class)->assertSuccessful();

    expect(Currency::all())
        ->toBeCollection()
        ->not->toBeEmpty();
});

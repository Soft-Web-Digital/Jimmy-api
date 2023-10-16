<?php

use App\Console\Commands\UpdateBanksCommand;
use App\Contracts\Fillers\BankFiller;
use App\Models\Bank;
use App\Models\Country;
use Illuminate\Support\Facades\App;

uses()->group('command', 'bank', 'external');





it('updates banks list successfully', function () {
    /** @var \App\Contracts\Fillers\BankFiller $service */
    $service = App::make(BankFiller::class);

    Country::factory()->create(['name' => $service::SUPPORTEDCOUNTRIES[0]]);

    test()->artisan(UpdateBanksCommand::class)->assertSuccessful();

    expect(Bank::all())
        ->toBeCollection()
        ->not->toBeEmpty();
});

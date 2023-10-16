<?php

use App\Contracts\Fillers\BankFiller;
use App\Models\Bank;
use App\Models\Country;
use Illuminate\Support\Facades\App;

uses()->group('contract', 'bank', 'external');





it('fills the banks in storage', function () {
    /** @var \App\Contracts\Fillers\BankFiller $service */
    $service = App::make(BankFiller::class);

    Country::factory()->create(['name' => $service::SUPPORTEDCOUNTRIES[0]]);

    $service->fillBanksInStorage();

    expect(Bank::all())
        ->toBeCollection()
        ->not->toBeEmpty();
});

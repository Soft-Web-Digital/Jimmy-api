<?php

use App\Contracts\Fillers\CurrencyFiller;
use App\Services\ApiLayer\ApiLayerCurrencyService;

uses()->group('service', 'external');





it('implements the CurrencyFiller contract', function () {
    $service = new ApiLayerCurrencyService();

    expect($service)->toBeInstanceOf(CurrencyFiller::class);
});

<?php

use App\Contracts\Fillers\BankFiller;
use App\Services\Paystack\PaystackBankService;

uses()->group('service', 'external');





it('implements the BankFiller contract', function () {
    $service = new PaystackBankService();

    expect($service)->toBeInstanceOf(BankFiller::class);
});

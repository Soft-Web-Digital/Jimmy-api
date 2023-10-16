<?php

use App\Contracts\CanVerifyBvn;
use App\Services\VerifiedAfrica\VerifiedAfricaBvnService;

uses()->group('service', 'external');





it('implements the CanVerifyBvn contract', function () {
    $service = new VerifiedAfricaBvnService();

    expect($service)->toBeInstanceOf(CanVerifyBvn::class);
});

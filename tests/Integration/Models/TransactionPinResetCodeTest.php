<?php

use App\Models\TransactionPinResetCode;

uses()->group('model', 'user');





it('affirms that the configured reset expiration minutes is an integer', function () {
    expect(TransactionPinResetCode::EXPIRATION_TIME_IN_MINUTES)->toBeInt();
});





it('affirms that the configured reset expiration minutes is greater than 1', function () {
    expect(TransactionPinResetCode::EXPIRATION_TIME_IN_MINUTES)->toBeGreaterThan(1);
});

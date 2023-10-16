<?php

use App\Models\TwoFaVerificationCode;

uses()->group('model', 'admin', 'user');





it('affirms that the configured two-fa expiration minutes is an integer', function () {
    expect(TwoFaVerificationCode::EXPIRATION_TIME_IN_MINUTES)->toBeInt();
});





it('affirms that the configured two-fa expiration minutes is greater than 1', function () {
    expect(TwoFaVerificationCode::EXPIRATION_TIME_IN_MINUTES)->toBeGreaterThan(1);
});

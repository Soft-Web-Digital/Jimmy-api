<?php

use App\Models\Currency;
use App\Services\CurrencyService;

uses()->group('service', 'currency');





it('update a currency\'s exchange rate for NGN', function () {
    $currency = Currency::factory()->create();

    $rate = fake()->randomFloat(2);
    $buy = fake()->randomFloat(2);
    $sell = fake()->randomFloat(2);

    (new CurrencyService())->update($currency, $rate, $buy, $sell);

    expect((float) $currency->exchange_rate_to_ngn)->toBe($rate);
    expect((float) $currency->buy_rate)->toBe($buy);
    expect((float) $currency->sell_rate)->toBe($sell);
});

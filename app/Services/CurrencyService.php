<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;

class CurrencyService
{
    /**
     * Update currency.
     *
     * @param \App\Models\Currency $currency
     * @param float|null $exchangeRateToNgn
     * @param float|null $buyRate
     * @param float|null $sellRate
     * @return \App\Models\Currency
     */
    public function update(
        Currency $currency,
        float|null $exchangeRateToNgn,
        float|null $buyRate,
        float|null $sellRate
    ): Currency {
        $currency->updateOrFail([
            'exchange_rate_to_ngn' => $exchangeRateToNgn ?? $currency->exchange_rate_to_ngn,
            'buy_rate' => $buyRate ?? $currency->buy_rate,
            'sell_rate' => $sellRate ?? $currency->sell_rate,
        ]);

        return $currency->refresh();
    }
}

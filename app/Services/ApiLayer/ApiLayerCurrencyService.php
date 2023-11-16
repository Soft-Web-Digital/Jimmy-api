<?php

declare(strict_types=1);

namespace App\Services\ApiLayer;

use App\Contracts\Fillers\CurrencyFiller;
use App\Models\Currency;
use Illuminate\Support\Str;

class ApiLayerCurrencyService extends ApiLayerBaseService implements CurrencyFiller
{
    /**
     * Fill the database with currencies.
     *
     * @return void
     */
    public function fillCurrenciesInStorage(): void
    {
        $apiLayerCurrencies = $this->connection()->get('/exchangerates_data/symbols');

        if ($apiLayerCurrencies->successful() && $apiLayerCurrencies->object()->success) {
            // @phpstan-ignore-next-line
            $currencies = collect($apiLayerCurrencies->object()->symbols)->map(function ($currency, $code) {
                return [
                    'id' => Str::orderedUuid()->toString(),
                    'code' => $code,
                    'name' => $currency,
                ];
            })->toArray();

            Currency::query()->upsert($currencies, ['code'], ['name']);
        }
    }
}

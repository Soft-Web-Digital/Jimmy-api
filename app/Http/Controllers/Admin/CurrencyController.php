<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Services\CurrencyService;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class CurrencyController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateCurrencyRequest $request
     * @param \App\Models\Currency $currency
     * @param \App\Services\CurrencyService $currencyService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(
        UpdateCurrencyRequest $request,
        Currency $currency,
        CurrencyService $currencyService
    ): Response {
        $currencyService->update(
            $currency,
            $request->exchange_rate_to_ngn,
            $request->buy_rate,
            $request->sell_rate,
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Currency updated successfully')
            ->withData([
                'currency' => $currency,
            ])
            ->build();
    }
}

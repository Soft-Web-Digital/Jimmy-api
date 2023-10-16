<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class CurrencyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Currency $currency
     */
    public function __construct(public Currency $currency)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request): Response
    {
        $currencies = QueryBuilder::for(
            $this->currency->query()->select([
                'id',
                'name',
                'code',
                'exchange_rate_to_ngn',
                'buy_rate',
                'sell_rate',
            ])
        )
            ->allowedFields([
                'id',
                'name',
                'code',
                'exchange_rate_to_ngn',
                'buy_rate',
                'sell_rate',
            ])
            ->defaultSort('code')
            ->allowedSorts([
                'code',
            ])
            ->allowedFilters([
                'code',
            ]);

        $currencies = $request->do_not_paginate
            ? $currencies->get()
            : $currencies->paginate((int) $request->per_page)->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Currencies fetched successfully')
            ->withData([
                'currencies' => $currencies,
            ])
            ->build();
    }
}

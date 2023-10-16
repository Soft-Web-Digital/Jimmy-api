<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Asset $asset
     */
    public function __construct(public Asset $asset)
    {
    }

    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request): Response
    {
        $assets = QueryBuilder::for(
            $this->asset->select([
                'id',
                'code',
                'name',
                'icon',
                'buy_rate',
                'sell_rate',
                'sell_min_amount',
                'sell_max_amount',
                'buy_min_amount',
                'buy_max_amount',
            ])
        )
            ->allowedFields([
                'id',
                'code',
                'name',
                'icon',
                'buy_rate',
                'sell_rate',
                'sell_min_amount',
                'sell_max_amount',
                'buy_min_amount',
                'buy_max_amount',
            ])
            ->allowedFilters([
                'code',
                'name',
                AllowedFilter::scope('network_id'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('networks', new IncludeSelectFields([
                    'id',
                    'name',
                    'wallet_address',
                ])),
            ])
            ->defaultSort('code');

        $assets = $request->do_not_paginate
            ? $assets->get()
            : $assets->paginate((int) $request->per_page)->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Assets fetched successfully.')
            ->withData([
                'assets' => $assets,
            ])
            ->build();
    }
}

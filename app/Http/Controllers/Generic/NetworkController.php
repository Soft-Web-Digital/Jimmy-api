<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Network;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class NetworkController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Network $network
     */
    public function __construct(public Network $network)
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
        $networks = QueryBuilder::for(
            $this->network->select([
                'id',
                'name',
                'wallet_address',
                'comment',
            ])
        )
            ->allowedFields([
                'id',
                'name',
                'wallet_address',
                'comment',
            ])
            ->allowedFilters([
                'name',
                AllowedFilter::scope('asset_id'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('assets', new IncludeSelectFields([
                    'id',
                    'code',
                    'name',
                    'icon',
                    'buy_rate',
                ])),
            ])
            ->defaultSort('name');

        $networks = (bool) $request->do_not_paginate
            ? $networks->get()
            : $networks->paginate((int) $request->do_not_paginate)->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Networks fetched successfully.')
            ->withData([
                'networks' => $networks,
            ])
            ->build();
    }
}

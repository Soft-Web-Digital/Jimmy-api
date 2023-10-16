<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\GiftcardProduct;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class GiftcardProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\GiftcardProduct $giftcardProduct
     */
    public function __construct(public GiftcardProduct $giftcardProduct)
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
        $giftcardProducts = QueryBuilder::for(
            $this->giftcardProduct->select([
                'id',
                'giftcard_category_id',
                'country_id',
                'currency_id',
                'name',
                'sell_rate',
                'sell_min_amount',
                'sell_max_amount',
                'buy_min_amount',
                'buy_max_amount',
                'activated_at',
            ])->whereHas('giftcardCategory', fn ($query) => $query->saleActivated())
        )
            ->allowedFields([
                'id',
                'giftcard_category_id',
                'country_id',
                'currency_id',
                'name',
                'sell_rate',
                'sell_min_amount',
                'sell_max_amount',
                'buy_min_amount',
                'buy_max_amount',
            ])
            ->defaultSort('name')
            ->allowedFilters([
                'name',
                AllowedFilter::exact('giftcard_category_id'),
                AllowedFilter::exact('country_id'),
                AllowedFilter::exact('currency_id'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('giftcardCategory', new IncludeSelectFields([
                    'id',
                    'name',
                    'icon',
                ])),
                AllowedInclude::custom('country', new IncludeSelectFields([
                    'id',
                    'name',
                    'flag_url',
                ])),
                AllowedInclude::custom('currency', new IncludeSelectFields([
                    'id',
                    'code',
                ])),
            ]);

        $giftcardProducts = (bool) $request->do_not_paginate
            ? $giftcardProducts->get()
            : $giftcardProducts->paginate((int) $request->per_page)->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard products fetched successfully')
            ->withData([
                'giftcard_products' => $giftcardProducts,
            ])
            ->build();
    }
}

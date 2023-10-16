<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\GiftcardCategory;
use App\Spatie\QueryBuilder\IncludeRelationCallback;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class GiftcardCategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\GiftcardCategory $giftcardcategory
     */
    public function __construct(public GiftcardCategory $giftcardcategory)
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
        $giftcardCategories = QueryBuilder::for(
            $this->giftcardcategory->select([
                'id',
                'name',
                'icon',
                'sale_term',
                'purchase_term',
                'sale_activated_at',
                'purchase_activated_at',
            ])
        )
            ->allowedFields([
                'id',
                'name',
                'icon',
                'sale_term',
                'purchase_term',
            ])
            ->defaultSort('name')
            ->allowedFilters([
                'name',
                AllowedFilter::scope('sale_activated'),
                AllowedFilter::scope('purchase_activated'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('countries', new IncludeRelationCallback(
                    fn ($query) => $query->select([
                        'id',
                        'name',
                        'flag_url',
                    ])->when(
                        isset($request->filter['sale_activated']),
                        fn ($query) => $query->giftcardActivated()
                    )
                ))
            ]);

        $giftcardCategories = (bool) $request->do_not_paginate
            ? $giftcardCategories->get()
            : $giftcardCategories->paginate((int) $request->per_page)->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard categories fetched successfully')
            ->withData([
                'giftcard_categories' => $giftcardCategories,
            ])
            ->build();
    }
}

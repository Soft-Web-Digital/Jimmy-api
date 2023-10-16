<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class CountryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Country $country
     */
    public function __construct(public Country $country)
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
        $countries = QueryBuilder::for(
            $this->country->query()->select([
                'id',
                'name',
                'alpha2_code',
                'alpha3_code',
                'dialing_code',
                'flag_url',
            ])
        )
            ->allowedFields([
                'id',
                'name',
                'alpha2_code',
                'alpha3_code',
                'flag_url',
                'dialing_code',
            ])
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'alpha2_code',
                'alpha3_code',
                'dialing_code',
            ])
            ->allowedFilters([
                'name',
                'dialing_code',
                AllowedFilter::exact('alpha2_code'),
                AllowedFilter::exact('alpha3_code'),
                AllowedFilter::scope('registration_activated'),
                AllowedFilter::scope('giftcard_activated'),
            ]);

        $countries = $request->do_not_paginate
            ? $countries->get()
            : $countries->paginate((int) $request->per_page)->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Countries fetched successfully')
            ->withData([
                'countries' => $countries,
            ])
            ->build();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class ReferralController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Referral $referral
     */
    public function __construct(public Referral $referral)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request): Response
    {
        $referrals = QueryBuilder::for($this->referral)
            ->allowedFields($this->referral->getQuerySelectables())
            ->allowedFilters([
                AllowedFilter::exact('paid'),
                AllowedFilter::scope('name'),
                AllowedFilter::scope('email'),
                AllowedFilter::scope('date'),
                AllowedFilter::scope('user_id'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('referred', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'avatar',
                ])),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'paid',
                'created_at',
                'updated_at',
            ]);

        $referrals = $request->do_not_paginate
            ? $referrals->get()
            : $referrals->paginate((int) $request->per_page)->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Referrals fetched successfully')
            ->withData([
                'referrals' => $referrals
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $referral
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(Request $request, string $referral): Response
    {
        $referral = QueryBuilder::for($this->referral)
            ->allowedIncludes([
                AllowedInclude::custom('referred', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ])),
            ])
            ->where(
                fn ($query) => $query->where('id', $referral)
            )
            ->firstOrFail();

        return ResponseBuilder::asSuccess()
            ->withMessage('Referral fetched successfully')
            ->withData([
                'referral' => $referral
            ])
            ->build();
    }
}

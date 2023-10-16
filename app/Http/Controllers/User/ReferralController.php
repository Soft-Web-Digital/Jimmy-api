<?php

namespace App\Http\Controllers\User;

use App\Enums\SystemDataCode;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\SystemData;
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
        $referrals = QueryBuilder::for(
            $this->referral->where('referee_id', $request->user()->id)
        )
            ->allowedFilters([
                AllowedFilter::exact('paid'),
                AllowedFilter::scope('name'),
                AllowedFilter::scope('email'),
                AllowedFilter::scope('date'),
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

        $systemData = SystemData::query()->where('code', 'REFMA')->first();
        return ResponseBuilder::asSuccess()
            ->withMessage('Referrals fetched successfully')
            ->withData([
                'referrals' => $referrals,
                'total_referrals' => $this->referral->where('referee_id', $request->user()->id)->count(),
                'total_trade' => $this->referral->where('referee_id', $request->user()->id)->whereHas('referred', function ($query) use ($systemData) {
                    $query->whereHas('assetTransactions', function ($query) use ($systemData) {
                        $query->where('amount', '>=', $systemData?->content ?? SystemDataCode::REFERRAL_MINIMUM_AMOUNT->defaultContent());
                    })->orWhereHas('giftcardTransactions', function ($query) use ($systemData) {
                        $query->where('amount', '>=', $systemData?->content ?? SystemDataCode::REFERRAL_MINIMUM_AMOUNT->defaultContent());
                    });
                })->count(),
                'total_reward' => (float) $this->referral->where('referee_id', $request->user()->id)->where('paid', true)->sum('amount'),
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
        $referral = QueryBuilder::for(
            $this->referral->where('referee_id', $request->user()->id)
        )
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

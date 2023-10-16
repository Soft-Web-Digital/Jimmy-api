<?php

namespace App\Http\Controllers\Generic;

use App\Exceptions\ExpectationFailedException;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class BankController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Bank $bank
     */
    public function __construct(public Bank $bank)
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
        try {
            $banks = QueryBuilder::for(
                $this->bank->select([
                    'id',
                    'name',
                ])
            )
                ->allowedFields([
                    'id',
                    'country_id',
                    'name',
                ])
                ->allowedFilters([
                    'name',
                    'country_id',
                ])
                ->allowedIncludes([
                    AllowedInclude::custom('country', new IncludeSelectFields([
                        'id',
                        'name',
                        'flag_url',
                    ])),
                ])
                ->defaultSort('name');

            $banks = (bool) $request->do_not_paginate
                ? $banks->get()
                : $banks->paginate((int) $request->do_not_paginate)->withQueryString();
        } catch (\Illuminate\Database\Eloquent\MissingAttributeException $e) {
            throw new ExpectationFailedException(
                str_contains($e->getMessage(), 'country_id')
                    ? 'You must include the country_id in the selected fields'
                    : $e->getMessage()
            );
        }

        return ResponseBuilder::asSuccess()
            ->withMessage('Banks fetched successfully.')
            ->withData([
                'banks' => $banks,
            ])
            ->build();
    }
}

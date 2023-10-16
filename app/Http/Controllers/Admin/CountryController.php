<?php

namespace App\Http\Controllers\Admin;

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
    public function index(Request $request): Response
    {
        $countries = QueryBuilder::for($this->country->query())
            ->allowedFields($this->country->getQuerySelectables())
            ->defaultSort('name')
            ->allowedSorts([
                'name',
                'alpha2_code',
                'alpha3_code',
            ])
            ->allowedFilters([
                'name',
                AllowedFilter::exact('alpha2_code'),
                AllowedFilter::exact('alpha3_code'),
                AllowedFilter::scope('registration_activated'),
                AllowedFilter::scope('giftcard_activated'),
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Countries fetched successfully')
            ->withData([
                'countries' => $countries,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Country $country
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(Country $country): Response
    {
        return ResponseBuilder::asSuccess()
            ->withMessage('Country fetched successfully')
            ->withData([
                'country' => $country,
            ])
            ->build();
    }

    /**
     * Toggle the specified resource for registration.
     *
     * @param \App\Models\Country $country
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleRegistrationActivation(Country $country): Response
    {
        $country->toggleRegistrationActivation();

        return ResponseBuilder::asSuccess()
            ->withMessage("Country's user registration usage updated successfully")
            ->withData([
                'country' => $country,
            ])
            ->build();
    }

    /**
     * Toggle the specified resource for giftcard.
     *
     * @param \App\Models\Country $country
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleGiftcardActivation(Country $country): Response
    {
        $country->toggleGiftcardActivation();

        return ResponseBuilder::asSuccess()
            ->withMessage("Country's giftcard usage updated successfully")
            ->withData([
                'country' => $country,
            ])
            ->build();
    }
}

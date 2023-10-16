<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\Models\GiftcardProductModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGiftcardProductRequest;
use App\Http\Requests\Admin\UpdateGiftcardProductRequest;
use App\Models\GiftcardProduct;
use App\Services\Giftcard\GiftcardProductService;
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
    public function index(Request $request): Response
    {
        $giftcardProducts = QueryBuilder::for($this->giftcardProduct->query())
            ->allowedFields($this->giftcardProduct->getQuerySelectables())
            ->allowedFilters([
                'name',
                AllowedFilter::scope('activated'),
                AllowedFilter::trashed(),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('giftcardCategory', new IncludeSelectFields([
                    'id',
                    'name',
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
            ])
            ->defaultSort('-updated_at')
            ->allowedSorts([
                'name',
                'activated_at',
                'created_at',
                'updated_at',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard products fetched successfully')
            ->withData([
                'giftcard_products' => $giftcardProducts,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreGiftcardProductRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(
        StoreGiftcardProductRequest $request,
        GiftcardProductService $giftcardProductService
    ): Response {
        $giftcardProduct = $giftcardProductService->create(
            (new GiftcardProductModelData())
                ->setGiftcardCategoryId($request->giftcard_category_id)
                ->setCountryId($request->country_id)
                ->setCurrencyId($request->currency_id)
                ->setName($request->name)
                ->setSellRate((float) $request->sell_rate)
                ->setSellMinAmount((float) $request->sell_min_amount)
                ->setSellMaxAmount((float) $request->sell_max_amount)
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Giftcard product created successfully')
            ->withData([
                'giftcard_product' => $giftcardProduct,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $giftcardProduct
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $giftcardProduct): Response
    {
        $giftcardProduct = QueryBuilder::for($this->giftcardProduct->query())
            ->allowedIncludes([
                AllowedInclude::custom('giftcardCategory', new IncludeSelectFields([
                    'id',
                    'name',
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
            ])
            ->findOrFail($giftcardProduct);

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard product fetched successfully')
            ->withData([
                'giftcard_product' => $giftcardProduct,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateGiftcardProductRequest $request
     * @param \App\Models\GiftcardProduct $giftcardProduct
     * @param \App\Services\Giftcard\GiftcardProductService $giftcardProductService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(
        UpdateGiftcardProductRequest $request,
        GiftcardProduct $giftcardProduct,
        GiftcardProductService $giftcardProductService
    ): Response {
        $giftcardProduct = $giftcardProductService->update(
            $giftcardProduct,
            (new GiftcardProductModelData())
                ->setGiftcardCategoryId($request->giftcard_category_id)
                ->setCountryId($request->country_id)
                ->setCurrencyId($request->currency_id)
                ->setName($request->name)
                ->setSellRate($request->sell_rate ? (float) $request->sell_rate : null)
                ->setSellMinAmount($request->sell_min_amount ? (float) $request->sell_min_amount : null)
                ->setSellMaxAmount($request->sell_max_amount ? (float) $request->sell_max_amount : null)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard product updated successfully')
            ->withData([
                'giftcard_product' => $giftcardProduct,
            ])
            ->build();
    }

    /**
     * Toggle activation of the specified resource from storage.
     *
     * @param \App\Models\GiftcardProduct $giftcardProduct
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleActivation(GiftcardProduct $giftcardProduct): Response
    {
        $giftcardProduct->toggleActivation();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard product activation updated successfully')
            ->withData([
                'giftcard_product' => $giftcardProduct,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\GiftcardProduct $giftcardProduct
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(GiftcardProduct $giftcardProduct): Response
    {
        $giftcardProduct->deleteOrFail();

        return response()->json(null, 204);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param \App\Models\GiftcardProduct $giftcardProduct
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restore(GiftcardProduct $giftcardProduct): Response
    {
        $giftcardProduct->restore();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard product restored successfully')
            ->withData([
                'giftcard_product' => $giftcardProduct,
            ])
            ->build();
    }
}

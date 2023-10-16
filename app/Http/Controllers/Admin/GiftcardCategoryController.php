<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\Models\GiftcardCategoryModelData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGiftcardCategoryRequest;
use App\Http\Requests\Admin\UpdateGiftcardCategoryRequest;
use App\Models\GiftcardCategory;
use App\Services\Giftcard\GiftcardCategoryService;
use App\Spatie\QueryBuilder\IncludeCountRelationCallback;
use App\Spatie\QueryBuilder\IncludeRelationCallback;
use App\Spatie\QueryBuilder\IncludeSelectFields;
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
     * @param \App\Models\GiftcardCategory $giftcardCategory
     */
    public function __construct(public GiftcardCategory $giftcardCategory)
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
        $giftcardCategories = QueryBuilder::for($this->giftcardCategory->query())
            ->allowedFields($this->giftcardCategory->getQuerySelectables())
            ->allowedFilters([
                'name',
                AllowedFilter::scope('sale_activated'),
                AllowedFilter::scope('purchase_activated'),
                AllowedFilter::trashed(),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('countries', new IncludeSelectFields([
                    'id',
                    'name',
                    'flag_url',
                ])),
                AllowedInclude::count('giftcardProductsCount'),
                AllowedInclude::custom('TgiftcardProductsCount', new IncludeCountRelationCallback(
                    fn ($query) => $query->withTrashed(),
                ), 'giftcardProducts'),
                AllowedInclude::count('adminsCount'),
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
            ->withMessage('Giftcard categories fetched successfully')
            ->withData([
                'giftcard_categories' => $giftcardCategories,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreGiftcardCategoryRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(
        StoreGiftcardCategoryRequest $request,
        GiftcardCategoryService $giftcardCategoryService
    ): Response {
        $giftcardCategory = $giftcardCategoryService->create(
            (new GiftcardCategoryModelData())
                ->setName($request->name)
                ->setIcon($request->file('icon'))
                ->setSaleTerm($request->sale_term)
                ->setCountryIds($request->countries)
                ->setAdminIds($request->admins)
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Giftcard category created successfully')
            ->withData([
                'giftcard_category' => $giftcardCategory,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $giftcardCategory
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $giftcardCategory): Response
    {
        $giftcardCategory = QueryBuilder::for($this->giftcardCategory->query())
            ->allowedIncludes([
                AllowedInclude::custom('countries', new IncludeSelectFields([
                    'id',
                    'name',
                    'flag_url',
                ])),
                'giftcardProducts',
                AllowedInclude::custom('TgiftcardProducts', new IncludeRelationCallback(
                    fn ($query) => $query->withTrashed(),
                ), 'giftcardProducts'),
                AllowedInclude::custom('admins', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ]))
            ])
            ->findOrFail($giftcardCategory);

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard category fetched successfully')
            ->withData([
                'giftcard_category' => $giftcardCategory,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateGiftcardCategoryRequest $request
     * @param \App\Models\GiftcardCategory $giftcardCategory
     * @param \App\Services\Giftcard\GiftcardCategoryService $giftcardCategoryService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(
        UpdateGiftcardCategoryRequest $request,
        GiftcardCategory $giftcardCategory,
        GiftcardCategoryService $giftcardCategoryService
    ): Response {
        $giftcardCategory = $giftcardCategoryService->update(
            $giftcardCategory,
            (new GiftcardCategoryModelData())
                ->setName($request->name)
                ->setIcon($request->file('icon'))
                ->setSaleTerm($request->sale_term)
                ->setPurchaseTerm($request->purchase_term)
                ->setCountryIds($request->countries)
                ->setAdminIds($request->admins)
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard category updated successfully')
            ->withData([
                'giftcard_category' => $giftcardCategory,
                'admins' => $giftcardCategory->admins()->get()
            ])
            ->build();
    }

    /**
     * Toggle sale activation of the specified resource from storage.
     *
     * @param \App\Models\GiftcardCategory $giftcardCategory
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleSaleActivation(GiftcardCategory $giftcardCategory): Response
    {
        $giftcardCategory->toggleSaleActivation();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard category sale activation updated successfully')
            ->withData([
                'giftcard_category' => $giftcardCategory,
            ])
            ->build();
    }

    /**
     * Toggle purchase activation of the specified resource from storage.
     *
     * @param \App\Models\GiftcardCategory $giftcardCategory
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function togglePurchaseActivation(GiftcardCategory $giftcardCategory): Response
    {
        $giftcardCategory->togglePurchaseActivation();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard category purchase activation updated successfully')
            ->withData([
                'giftcard_category' => $giftcardCategory,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\GiftcardCategory $giftcardCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(GiftcardCategory $giftcardCategory): Response
    {
        $giftcardCategory->deleteOrFail();

        return response()->json(null, 204);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param \App\Models\GiftcardCategory $giftcardCategory
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restore(GiftcardCategory $giftcardCategory): Response
    {
        $giftcardCategory->restore();

        return ResponseBuilder::asSuccess()
            ->withMessage('Giftcard category restored successfully')
            ->withData([
                'giftcard_category' => $giftcardCategory,
            ])
            ->build();
    }
}

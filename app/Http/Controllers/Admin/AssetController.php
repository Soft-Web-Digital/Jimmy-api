<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssetRequest;
use App\Http\Requests\Admin\UpdateAssetRequest;
use App\Models\Asset;
use App\Services\Crypto\AssetService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Asset $asset
     */
    public function __construct(public Asset $asset)
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
        $assets = QueryBuilder::for($this->asset->query())
            ->allowedFields($this->asset->getQuerySelectables())
            ->allowedFilters([
                'name',
                'code',
                AllowedFilter::trashed(),
            ])
            ->allowedIncludes([
                'networks',
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Assets fetched successfully')
            ->withData([
                'assets' => $assets,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreAssetRequest $request
     * @param \App\Services\Crypto\AssetService $assetService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(StoreAssetRequest $request, AssetService $assetService): Response
    {
        $asset = $assetService->create(
            $request->code,
            $request->name,
            $request->file('icon'),
            $request->buy_rate,
            $request->sell_rate,
            $request->networks,
            $request->buy_min_amount,
            $request->buy_max_amount,
            $request->sell_min_amount,
            $request->sell_max_amount
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Asset created successfully')
            ->withData([
                'asset' => $asset,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $asset
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $asset): Response
    {
        $asset = QueryBuilder::for($this->asset->query())
            ->allowedIncludes([
                'networks',
            ])
            ->findOrFail($asset);

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset fetched successfully')
            ->withData([
                'asset' => $asset,
            ])
            ->build();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Admin\UpdateAssetRequest $request
     * @param \App\Models\Asset $asset
     * @param \App\Services\Crypto\AssetService $assetService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateAssetRequest $request, Asset $asset, AssetService $assetService): Response
    {
        $asset = $assetService->update(
            $asset,
            $request->code,
            $request->name,
            $request->file('icon'),
            $request->buy_rate,
            $request->sell_rate,
            $request->networks,
            $request->buy_min_amount,
            $request->buy_max_amount,
            $request->sell_min_amount,
            $request->sell_max_amount
        );

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset updated successfully')
            ->withData([
                'asset' => $asset,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Asset $asset
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Asset $asset): Response
    {
        $asset->deleteOrFail();

        return response()->json(null, 204);
    }

    /**
     * Restore the specified resource from storage.
     *
     * @param \App\Models\Asset $asset
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restore(Asset $asset): Response
    {
        $asset->restore();

        return ResponseBuilder::asSuccess()
            ->withMessage('Asset restored successfully')
            ->withData([
                'asset' => $asset,
            ])
            ->build();
    }
}

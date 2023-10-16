<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Models\Banner;
use App\Services\BannerService;
use App\Spatie\QueryBuilder\IncludeSelectFields;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class BannerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Models\Banner $banner
     */
    public function __construct(public Banner $banner)
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
        $banners = QueryBuilder::for($this->banner->query())
            ->allowedFilters([
                AllowedFilter::exact('admin_id'),
                AllowedFilter::scope('activated'),
            ])
            ->allowedIncludes([
                AllowedInclude::custom('admin', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ]))
            ])
            ->paginate((int) $request->per_page)
            ->withQueryString();

        return ResponseBuilder::asSuccess()
            ->withMessage('Banners fetched successfully')
            ->withData([
                'banners' => $banners,
            ])
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Admin\StoreBannerRequest $request
     * @param \App\Services\BannerService $bannerService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(StoreBannerRequest $request, BannerService $bannerService): Response
    {
        $banner = $bannerService->create(
            $request->user('api_admin'), // @phpstan-ignore-line
            $request->file('preview_image'),
            $request->file('featured_image')
        );

        return ResponseBuilder::asSuccess()
            ->withHttpCode(201)
            ->withMessage('Banner created successfully')
            ->withData([
                'banner' => $banner,
            ])
            ->build();
    }

    /**
     * Display the specified resource.
     *
     * @param string $banner
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(string $banner): Response
    {
        $banner = QueryBuilder::for($this->banner)
            ->allowedIncludes([
                AllowedInclude::custom('admin', new IncludeSelectFields([
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ]))
            ])
            ->findOrFail($banner);

        return ResponseBuilder::asSuccess()
            ->withMessage('Banner fetched successfully')
            ->withData([
                'banner' => $banner,
            ])
            ->build();
    }

    /**
     * Toggle the specified resource for activation.
     *
     * @param \App\Models\Banner $banner
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toggleActivation(Banner $banner): Response
    {
        $banner->toggleActivation();

        return ResponseBuilder::asSuccess()
            ->withMessage('Banner activation status updated successfully')
            ->withData([
                'banner' => $banner,
            ])
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Banner $banner
     * @param \App\Services\BannerService $bannerService
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Banner $banner, BannerService $bannerService): Response
    {
        $bannerService->delete($banner);

        return response()->json(null, 204);
    }
}

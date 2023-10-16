<?php

namespace App\Http\Controllers\Generic;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
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
     * Handle the incoming request.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(): Response
    {
        $banners = $this->banner
            ->activated()
            ->select([
                'id',
                'preview_image',
                'featured_image',
                'created_at',
                'updated_at',
            ])
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return ResponseBuilder::asSuccess()
            ->withMessage('Banners fetched successfully.')
            ->withData([
                'banners' => $banners,
            ])
            ->build();
    }
}

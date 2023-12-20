<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ExpectationFailedException;
use App\Models\Admin;
use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BannerService
{
    /**
     * Create banner.
     *
     * @param \App\Models\Admin $admin
     * @param \Illuminate\Http\UploadedFile $previewImage
     * @param \Illuminate\Http\UploadedFile $featuredImage
     * @return \App\Models\Banner
     */
    public function create(Admin $admin, UploadedFile $previewImage, UploadedFile $featuredImage): Banner
    {
        $previewImagePath = $previewImage->store('banner');
        throw_if(
            $previewImagePath === false,
            ExpectationFailedException::class,
            'Preview image could not be uploaded'
        );

        $featuredImagePath = $featuredImage->store('banner');
        throw_if(
            $featuredImagePath === false,
            ExpectationFailedException::class,
            'Featured image could not be uploaded'
        );

        $previewImage = saveFileAndReturnPath($previewImage);
        $featuredImage = saveFileAndReturnPath($featuredImage);

        return Banner::query()->create([
            'admin_id' => $admin->id,
            'preview_image' => $previewImage,
            'featured_image' => $featuredImage,
            'activated_at' => now(),
        ])->refresh();
    }

    /**
     * Delete the banner.
     *
     * @param \App\Models\Banner $banner
     * @return void
     */
    public function delete(Banner $banner): void
    {
        Storage::delete([
            $banner->preview_image,
            $banner->featured_image,
        ]);

        $banner->delete();
    }
}

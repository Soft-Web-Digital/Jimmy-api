<?php

use App\Models\Admin;
use App\Models\Banner;
use App\Services\BannerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses()->group('service', 'banner');




it('can create a banner', function () {
    Storage::fake();

    $banner = (new BannerService())->create(
        Admin::factory()->create(),
        UploadedFile::fake()->image('banner', 100, 100),
        UploadedFile::fake()->image('banner', 500, 500),
    );

    expect($banner)
        ->toBeInstanceOf(Banner::class)
        ->preview_image->not->toBeEmpty()
        ->featured_image->not->toBeEmpty();
});





it('can delete a banner', function () {
    Storage::fake();

    $banner = (new BannerService())->create(
        Admin::factory()->create(),
        UploadedFile::fake()->image('banner', 100, 100),
        UploadedFile::fake()->image('banner', 500, 500),
    );

    $previewImage = $banner->preview_image;
    $featuredImage = $banner->featured_image;

    (new BannerService())->delete($banner);

    Storage::assertMissing([
        $previewImage,
        $featuredImage
    ]);

    test()->assertDatabaseCount('banners', 0);
});

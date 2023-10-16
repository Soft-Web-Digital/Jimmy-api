<?php

use App\DataTransferObjects\Models\GiftcardCategoryModelData;
use App\Enums\GiftcardServiceProvider;
use App\Models\Admin;
use App\Models\Country;
use App\Models\GiftcardCategory;
use App\Services\Giftcard\GiftcardCategoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses()->group('service', 'giftcard-category');





it('can create a giftcard category', function () {
    $modelData = (new GiftcardCategoryModelData())
        ->setName(fake()->word())
        ->setSaleTerm(fake()->sentence());

    expect((new GiftcardCategoryService())->create($modelData))->toBeInstanceOf(GiftcardCategory::class);
});





it('can upload an icon on giftcard category creation', function () {
    Storage::fake();

    $icon = UploadedFile::fake()->image('icon.jpg');

    $modelData = (new GiftcardCategoryModelData())
        ->setName(fake()->domainWord())
        ->setSaleTerm(fake()->sentence())
        ->setIcon($icon);

    (new GiftcardCategoryService())->create($modelData);

    $iconName = str_replace(' ', '', "GCC{$modelData->getName()}");

    Storage::assertExists(strtolower("icons/{$iconName}.{$icon->extension()}"));
});





it('can sync countries for a giftcard category on creation', function () {
    $modelData = (new GiftcardCategoryModelData())
        ->setName(fake()->word())
        ->setSaleTerm(fake()->sentence())
        ->setCountryIds(Country::factory()->count(5)->create()->pluck('id')->toArray());

    expect((new GiftcardCategoryService())->create($modelData)->countries()->count())->toBe(5);
});





it('can sync admins for a giftcard category on creation', function () {
    $modelData = (new GiftcardCategoryModelData())
        ->setName(fake()->word())
        ->setSaleTerm(fake()->sentence())
        ->setAdminIds(Admin::factory()->count(5)->create()->pluck('id')->toArray());

    expect((new GiftcardCategoryService())->create($modelData)->admins()->count())->toBe(5);
});





it('can update a giftcard category', function () {
    $modelData = (new GiftcardCategoryModelData())
        ->setName(fake()->word())
        ->setSaleTerm(fake()->sentence());

    $giftcardCategory = GiftcardCategory::factory()->create()->refresh();

    expect((new GiftcardCategoryService())->update($giftcardCategory, $modelData))
        ->name->toBe($modelData->getName())
        ->sale_term->toBe($modelData->getSaleTerm())
        ->icon->toBe($giftcardCategory->icon);
});





it('cannot update the name of a serviced giftcard category', function () {
    $modelData = (new GiftcardCategoryModelData())->setName(fake()->word());

    $giftcardCategory = GiftcardCategory::factory()->create([
        'service_provider' => GiftcardServiceProvider::WAVERLITE->value,
    ])->refresh();

    expect((new GiftcardCategoryService())->update($giftcardCategory, $modelData))
        ->name->toBe($giftcardCategory->name);
});





it('can upload an icon on giftcard category update', function () {
    Storage::fake();

    $icon = UploadedFile::fake()->image('icon.jpg');

    $modelData = (new GiftcardCategoryModelData())->setIcon($icon);

    $giftcardCategory = GiftcardCategory::factory()->create()->refresh();

    (new GiftcardCategoryService())->update($giftcardCategory, $modelData);

    $iconName = str_replace(' ', '', "GCC{$giftcardCategory->name}");

    Storage::assertExists(strtolower("icons/{$iconName}.{$icon->extension()}"));
});





it('can toggles countries for a giftcard category on update', function () {
    $countries = Country::factory()->count(5)->create()->pluck('id')->toArray();

    $modelData = (new GiftcardCategoryModelData())
        ->setName(fake()->word())
        ->setSaleTerm(fake()->sentence())
        ->setCountryIds($countries);

    $giftcardCategory = GiftcardCategory::factory()->create()->refresh();
    $giftcardCategory->countries()->sync(array_slice($countries, 1));

    expect($giftcardCategory->countries()->count())->toBe(4);

    expect((new GiftcardCategoryService())->update($giftcardCategory, $modelData)->countries()->count())->toBe(1);
});





it('can toggles admins for a giftcard category on update', function () {
    $admins = Admin::factory()->count(5)->create()->pluck('id')->toArray();

    $modelData = (new GiftcardCategoryModelData())
        ->setName(fake()->word())
        ->setSaleTerm(fake()->sentence())
        ->setAdminIds($admins);

    $giftcardCategory = GiftcardCategory::factory()->create()->refresh();
    $giftcardCategory->admins()->sync(array_slice($admins, 1));

    expect($giftcardCategory->admins()->count())->toBe(4);

    expect((new GiftcardCategoryService())->update($giftcardCategory, $modelData)->admins()->count())->toBe(5);
});

<?php

use App\DataTransferObjects\Models\GiftcardProductModelData;
use App\Enums\GiftcardServiceProvider;
use App\Models\Country;
use App\Models\Currency;
use App\Models\GiftcardCategory;
use App\Models\GiftcardProduct;
use App\Services\Giftcard\GiftcardProductService;

uses()->group('service', 'giftcard-product');





it('can create a giftcard product', function () {
    $modelData = (new GiftcardProductModelData())
        ->setCountryId(Country::factory()->create()->id)
        ->setCurrencyId(Currency::factory()->create()->id)
        ->setGiftcardCategoryId(GiftcardCategory::factory()->create()->id)
        ->setName(fake()->domainWord())
        ->setSellRate(fake()->randomFloat(2, 100, 1000))
        ->setSellMinAmount(fake()->randomFloat(2, 100, 1000))
        ->setSellMaxAmount(fake()->randomFloat(2, 100, 1000));

    expect((new GiftcardProductService())->create($modelData))->toBeInstanceOf(GiftcardProduct::class);
});





it('can update a giftcard product', function () {
    $giftcardProduct = GiftcardProduct::factory()->create()->refresh();

    $modelData = (new GiftcardProductModelData())
        ->setSellRate(fake()->randomFloat(2, 100, 1000))
        ->setSellMinAmount(fake()->randomFloat(2, 100, 1000))
        ->setSellMaxAmount(fake()->randomFloat(2, 100, 1000));

    expect((new GiftcardProductService())->update($giftcardProduct, $modelData))
        ->toBeInstanceOf(GiftcardProduct::class);
});





it('cannot update a serviced giftcard product', function () {
    $giftcardProduct = GiftcardProduct::factory()
        ->create(['service_provider' => GiftcardServiceProvider::WAVERLITE->value])
        ->refresh();
    $name = $giftcardProduct->name;

    $modelData = (new GiftcardProductModelData())
        ->setName(fake()->word());

    expect((new GiftcardProductService())->update($giftcardProduct, $modelData))
        ->name->toBe($name);
});

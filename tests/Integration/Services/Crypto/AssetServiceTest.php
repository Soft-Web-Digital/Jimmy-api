<?php

use App\Models\Asset;
use App\Models\Network;
use App\Services\Crypto\AssetService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses()->group('service', 'asset');





it('can create an asset without any networks', function () {
    Storage::fake();

    $icon = UploadedFile::fake()->image('icons.jpg');

    $assetService = (new AssetService())->create(
        'ETH',
        'Ethereum',
        $icon,
        fake()->randomFloat(2, 10, 100),
        fake()->randomFloat(2, 10, 100)
    );

    Storage::assertExists(strtolower("icons/CRY{$assetService->code}.{$icon->extension()}"));

    expect($assetService)
        ->toBeInstanceOf(Asset::class)
        ->id->toBe(Asset::query()->where('id', $assetService->id)->value('id'));
});





it('can create an asset with networks', function () {
    Storage::fake();

    $networks = Network::factory()->count(1)->create()->pluck('id')->toArray();

    $assetService = (new AssetService())->create(
        'ETH',
        'Ethereum',
        UploadedFile::fake()->image('icons.jpg'),
        fake()->randomFloat(2, 10, 100),
        fake()->randomFloat(2, 10, 100),
        $networks
    );

    expect($assetService->networks()->pluck('id')->toArray() === $networks)->toBeTrue();
});





it('can update an asset', function ($data) {
    $attribute = $data['attribute'];
    $value = $data['value'];

    $asset = Asset::factory()->create();

    $assetService = match ($attribute) {
        'code' => (new AssetService())->update(asset: $asset, code: $value),
        'name' => (new AssetService())->update(asset: $asset, name: $value),
        'buy_rate' => (new AssetService())->update(asset: $asset, buyRate: $value),
        'sell_rate' => (new AssetService())->update(asset: $asset, sellRate: $value),
    };

    expect($asset->$attribute)->toBe($assetService->$attribute);
})->with([
    'code' => fn () => [
        'attribute' => 'code',
        'value' => fake()->currencyCode(),
    ],
    'name' => fn () => [
        'attribute' => 'name',
        'value' => fake()->currencyCode(),
    ],
    'buy_rate' => fn () => [
        'attribute' => 'buy_rate',
        'value' => fake()->randomFloat(2, 10, 100),
    ],
    'sell_rate' => fn () => [
        'attribute' => 'sell_rate',
        'value' => fake()->randomFloat(2, 10, 100),
    ],
]);

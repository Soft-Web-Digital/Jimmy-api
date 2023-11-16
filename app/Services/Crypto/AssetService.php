<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Exceptions\ExpectationFailedException;
use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssetService
{
    /**
     * Create a asset.
     *
     * @param string $code
     * @param string $name
     * @param UploadedFile $icon
     * @param float $buyRate
     * @param float $sellRate
     * @param array<int, string>|null $networkIds
     * @param float|null $minBuyAmount
     * @param float|null $maxBuyAmount
     * @param float|null $minSellAmount
     * @param float|null $maxSellAmount
     * @return Asset
     */
    public function create(
        string $code,
        string $name,
        UploadedFile $icon,
        float $buyRate,
        float $sellRate,
        array|null $networkIds = null,
        float|null $minBuyAmount = null,
        float|null $maxBuyAmount = null,
        float|null $minSellAmount = null,
        float|null $maxSellAmount = null
    ): Asset {
        // $path = $icon->storeAs('icons', strtolower(
        //     'CRY' . str_replace(' ', '', $code)
        //     . '.' . $icon->extension()
        // ));

        // throw_if($path === false, ExpectationFailedException::class, 'Icon could not be uploaded');

        // $icon = Storage::url($path);

        $icon = saveFileAndReturnPath($icon);

        DB::beginTransaction();

        try {
            /** @var \App\Models\Asset $asset */
            $asset = Asset::query()->create([
                'code' => $code,
                'name' => $name,
                'icon' => $icon,
                'buy_rate' => $buyRate,
                'sell_rate' => $sellRate,
                'buy_min_amount' => $minBuyAmount,
                'buy_max_amount' => $maxBuyAmount,
                'sell_min_amount' => $minSellAmount,
                'sell_max_amount' => $maxSellAmount,
            ]);

            if ($networkIds) {
                $asset->networks()->sync($networkIds);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $asset->refresh();
    }

    /**
     * Update the asset.
     *
     * @param \App\Models\Asset $asset
     * @param string|null $code
     * @param string|null $name
     * @param \Illuminate\Http\UploadedFile|null $icon
     * @param float|null $buyRate
     * @param float|null $sellRate
     * @param array<int, string>|null $networkIds
     * @param float|null $minBuyAmount
     * @param float|null $maxBuyAmount
     * @param float|null $minSellAmount
     * @param float|null $maxSellAmount
     * @return \App\Models\Asset
     */
    public function update(
        Asset $asset,
        string $code = null,
        string $name = null,
        UploadedFile $icon = null,
        float $buyRate = null,
        float $sellRate = null,
        array|null $networkIds = null,
        float|null $minBuyAmount = null,
        float|null $maxBuyAmount = null,
        float|null $minSellAmount = null,
        float|null $maxSellAmount = null
    ): Asset {
        if ($icon instanceof UploadedFile) {
            // $path = $icon->storeAs('icons', strtolower(
            //     'CRY' . str_replace(' ', '', $code ?? $asset->code)
            //     . '.' . $icon->extension()
            // ));

            // throw_if($path === false, ExpectationFailedException::class, 'Icon could not be uploaded');

            // $icon = Storage::url($path);

            $icon = saveFileAndReturnPath($icon);
        }

        DB::beginTransaction();

        try {
            $asset->updateOrFail([
                'code' => $code ?? $asset->code,
                'name' => $name ?? $asset->name,
                'icon' => $icon ?? $asset->icon,
                'buy_rate' => $buyRate ?? $asset->buy_rate,
                'sell_rate' => $sellRate ?? $asset->sell_rate,
                'buy_min_amount' => $minBuyAmount ?? $asset->buy_min_amount,
                'buy_max_amount' => $maxBuyAmount ?? $asset->buy_max_amount,
                'sell_min_amount' => $minSellAmount ?? $asset->sell_min_amount,
                'sell_max_amount' => $maxSellAmount ?? $asset->sell_max_amount,
            ]);

            if ($networkIds) {
                $asset->networks()->sync($networkIds);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $asset->refresh();
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Giftcard;

use App\DataTransferObjects\Models\GiftcardProductModelData;
use App\Models\GiftcardProduct;

class GiftcardProductService
{
    /**
     * Create a giftcard product.
     *
     * @param \App\DataTransferObjects\Models\GiftcardProductModelData $giftcardProductModelData
     * @return \App\Models\GiftcardProduct
     */
    public function create(GiftcardProductModelData $giftcardProductModelData): GiftcardProduct
    {
        return GiftcardProduct::query()->create([
            'giftcard_category_id' => $giftcardProductModelData->getGiftcardCategoryId(),
            'country_id' => $giftcardProductModelData->getCountryId(),
            'currency_id' => $giftcardProductModelData->getCurrencyId(),
            'name' => $giftcardProductModelData->getName(),
            'sell_rate' => $giftcardProductModelData->getSellRate(),
            'sell_min_amount' => $giftcardProductModelData->getSellMinAmount(),
            'sell_max_amount' => $giftcardProductModelData->getSellMaxAmount(),
            'activated_at' => now(),
        ])->refresh();
    }

    /**
     * Update the giftcard product.
     *
     * @param \App\Models\GiftcardProduct $giftcardProduct
     * @param \App\DataTransferObjects\Models\GiftcardProductModelData $giftcardProductModelData
     * @return \App\Models\GiftcardProduct
     */
    public function update(
        GiftcardProduct $giftcardProduct,
        GiftcardProductModelData $giftcardProductModelData
    ): GiftcardProduct {
        $giftcardProduct->updateOrFail([
            'giftcard_category_id' => (bool) $giftcardProduct->service_provider
                ? $giftcardProduct->giftcard_category_id
                : ($giftcardProductModelData->getGiftcardCategoryId() ?? $giftcardProduct->giftcard_category_id),
            'country_id' => (bool) $giftcardProduct->service_provider
                ? $giftcardProduct->country_id
                : ($giftcardProductModelData->getCountryId() ?? $giftcardProduct->country_id),
            'currency_id' => (bool) $giftcardProduct->service_provider
                ? $giftcardProduct->currency_id
                : ($giftcardProductModelData->getCurrencyId() ?? $giftcardProduct->currency_id),
            'name' => (bool) $giftcardProduct->service_provider
                ? $giftcardProduct->name
                : ($giftcardProductModelData->getName() ?? $giftcardProduct->name),
            'sell_rate' => $giftcardProductModelData->getSellRate() ?? $giftcardProduct->sell_rate,
            'sell_min_amount' => $giftcardProductModelData->getSellMinAmount() ?? $giftcardProduct->sell_min_amount,
            'sell_max_amount' => $giftcardProductModelData->getSellMaxAmount() ?? $giftcardProduct->sell_max_amount,
        ]);

        return $giftcardProduct->refresh();
    }
}

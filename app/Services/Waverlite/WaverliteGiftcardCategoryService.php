<?php

declare(strict_types=1);

namespace App\Services\Waverlite;

use App\Contracts\Fillers\GiftcardCategoryFiller;
use App\Enums\GiftcardServiceProvider;
use App\Models\GiftcardCategory;
use Illuminate\Support\Str;

class WaverliteGiftcardCategoryService extends WaverliteBaseService implements GiftcardCategoryFiller
{
    /**
     * Fill the database with giftcard categories.
     *
     * @return void
     */
    public function fillGiftcardCategoriesInStorage(): void
    {
        $categories = array_map(function ($category) {
            return [
                'id' => Str::orderedUuid()->toString(),
                'name' => $category,
                'service_provider' => GiftcardServiceProvider::WAVERLITE->value,
                'purchase_activated_at' => date('Y-m-d H:i:s'),
            ];
        }, $this->getCategories());

        GiftcardCategory::query()->upsert($categories, ['name', 'service_provider']);
    }

    /**
     * Get the giftcard categories.
     *
     * @return array<int, string>
     */
    public function getCategories(): array
    {
        $response = $this->connection()->get('/1.0/payout/gift-card/categories/list');

        if ($response->successful() && $response->object()->state) {
            return $response->object()->data;
        }

        return [];
    }
}

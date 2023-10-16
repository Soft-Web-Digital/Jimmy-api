<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Fillers\GiftcardCategoryFiller;
use App\Contracts\Fillers\GiftcardProductFiller;
use App\Services\Waverlite\WaverliteGiftcardCategoryService;
use App\Services\Waverlite\WaverliteGiftcardProductService;
use App\Traits\EnumTrait;

enum GiftcardServiceProvider: string
{
    use EnumTrait;

    case WAVERLITE = 'waverlite';

    /**
     * Get the category filler.
     *
     * @return \App\Contracts\Fillers\GiftcardCategoryFiller|null
     */
    public function categoryFiller(): GiftcardCategoryFiller|null
    {
        return match ($this) {
            self::WAVERLITE => new WaverliteGiftcardCategoryService(),
        };
    }

    /**
     * Get the product filler.
     *
     * @return \App\Contracts\Fillers\GiftcardProductFiller|null
     */
    public function productFiller(): GiftcardProductFiller|null
    {
        return match ($this) {
            self::WAVERLITE => new WaverliteGiftcardProductService(),
        };
    }
}

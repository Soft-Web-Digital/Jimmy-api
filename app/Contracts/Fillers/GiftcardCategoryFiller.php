<?php

namespace App\Contracts\Fillers;

interface GiftcardCategoryFiller
{
    /**
     * Fill the database with giftcard categories.
     *
     * @return void
     */
    public function fillGiftcardCategoriesInStorage(): void;
}

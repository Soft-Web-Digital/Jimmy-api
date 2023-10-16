<?php

namespace App\Contracts\Fillers;

interface GiftcardProductFiller
{
    /**
     * Fill the database with giftcard products.
     *
     * @return void
     */
    public function fillGiftcardProductsInStorage(): void;
}

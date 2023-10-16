<?php

declare(strict_types=1);

namespace App\Services\Waverlite;

use App\Contracts\Fillers\GiftcardProductFiller;
use App\Enums\Queue;
use App\Jobs\SaveWaverliteGiftcardProductJob;

class WaverliteGiftcardProductService extends WaverliteBaseService implements GiftcardProductFiller
{
    /**
     * Fill the database with giftcard products.
     *
     * @return void
     */
    public function fillGiftcardProductsInStorage(): void
    {
        $products = $this->getProducts();

        foreach ($products as $product) {
            dispatch(new SaveWaverliteGiftcardProductJob($product))->onQueue(Queue::CRITICAL->value);
        }
    }

    /**
     * Get the giftcard products.
     *
     * @return array<int, mixed>
     */
    public function getProducts(): array
    {
        $response = $this->connection()->get('/1.0/payout/gift-card/list');

        if ($response->successful() && $response->object()->state) { // @phpstan-ignore-line
            return $response->object()->data; // @phpstan-ignore-line
        }

        return [];
    }
}

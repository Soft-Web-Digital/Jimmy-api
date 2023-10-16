<?php

namespace App\Exports;

use App\Models\Giftcard;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Throwable;

class GiftcardExport implements WithMultipleSheets
{
    /**
     * @return array<int, mixed>
     * @throws Throwable
     */
    public function sheets(): array
    {
        $sheets = [];

        $giftcards = Giftcard::query()
            ->with([
                'giftcardProduct:id,name,giftcard_category_id',
                'giftcardProduct.giftcardCategory:id,name',
                'user:id,firstname,lastname'
            ])
            ->whereNull('parent_id')
            ->latest()
            ->take($this->chunkSize())
            ->get();

        $sheets[] = new GiftcardSheet($giftcards, 1);
        return $sheets;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 500;
    }
}

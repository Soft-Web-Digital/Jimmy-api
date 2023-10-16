<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GiftcardSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $giftcards, protected int $sheetIndex)
    {
    }

    public function collection(): Collection
    {
        return $this->giftcards;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Name',
            'Reference No',
            'Category',
            'Product',
            'Amount in Naira',
            'Amount in Currency',
            'Date',
            'Trade Type',
            'Status'
        ];
    }

    /**
     * @param $row
     * @return array<int, string>
     */
    public function map($row): array
    {
        return [
            $row->user->firstname . ' ' . $row->user->lastname,
            $row->reference,
            $row->giftcardProduct->giftcardCategory->name,
            $row->giftcardProduct->name,
            $row->payable_amount,
            $row->amount,
            $row->created_at ? $row->created_at->format('M d, Y') : '',
            $row->trade_type->value,
            $row->status->value
        ];
    }
}

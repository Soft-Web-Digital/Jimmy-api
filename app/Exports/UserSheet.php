<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $users, protected int $sheetIndex)
    {
    }

    public function collection(): Collection
    {
        return $this->users;
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Wallet Balance',
            'Date Joined',
        ];
    }

    /**
     * @param $row
     * @return array<int, string>
     */
    public function map($row): array
    {
        return [
            $row->firstname . ' ' . $row->lastname,
            $row->email,
            $row->phone_number,
            round($row->wallet_balance, 2),
            $row->created_at ? $row->created_at->format('M d, Y') : '',
        ];
    }
}

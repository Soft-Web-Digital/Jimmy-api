<?php

namespace App\Exports;

use App\Models\Giftcard;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Exception;
use Throwable;

class DataExport implements WithMultipleSheets, WithChunkReading
{
    public function __construct(
        protected mixed $model,
        protected int $total,
        protected string $sheet,
        protected int $offset,
        protected int $limit
    ) {
    }

    /**
     * @return array<int, mixed>
     * @throws Throwable
     */
    public function sheets(): array
    {
        throw_if($this->total < 1, Exception::class, 'Empty set');
        $sheets = [];
        $chunks = ceil($this->limit / $this->chunkSize());

        for ($page = 1; $page <= $chunks; $page++) {
            $offset = ($page - 1) * $this->chunkSize();
            $model = $this->model::latest();
            if ($this->model == Giftcard::class) {
                $model = $this->model::with([
                    'user:id,firstname,lastname',
                    'giftcardProduct:id,name,giftcard_category_id',
                    'giftcardProduct.giftcardCategory:id,name'
                ])->latest();
            }
            $data = $model->skip(($this->offset * $this->limit) + $offset)->take($this->chunkSize())->get();
            $sheets[] = new $this->sheet($data, $page);
        }

        return $sheets;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}

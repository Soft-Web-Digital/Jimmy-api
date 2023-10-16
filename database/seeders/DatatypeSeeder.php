<?php

namespace Database\Seeders;

use App\Enums\DatatypeEnum;
use App\Models\Datatype;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatatypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datatypes = array_map(function ($datatype) {
            return [
                'id' => Str::orderedUuid()->toString(),
                'name' => $datatype->value,
                'hint' => $datatype->hint(),
                'developer_hint' => $datatype->developerHint(),
                'rule' => $datatype->rule(),
            ];
        }, DatatypeEnum::cases());

        Datatype::query()->upsert(
            $datatypes,
            ['name'],
            ['hint', 'developer_hint', 'rule']
        );
    }
}

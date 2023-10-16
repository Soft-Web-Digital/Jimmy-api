<?php

namespace Database\Seeders;

use App\Enums\SystemDataCode;
use App\Models\Datatype;
use App\Models\SystemData;
use Illuminate\Database\Seeder;

class SystemDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $systemDataCodes = SystemDataCode::cases();

        /** @var \Illuminate\Database\Eloquent\Collection $datatypes */
        $datatypes = Datatype::query()->select(['id', 'name'])
            ->whereIn('name', array_map(fn ($systemDataCode) => $systemDataCode->datatype()->value, $systemDataCodes))
            ->get();

        foreach ($systemDataCodes as $systemDataCode) {
            $systemData = SystemData::query()->where('code', $systemDataCode->value)->firstOrNew([]);
            $systemData->code = $systemDataCode->value;
            $systemData->title = $systemDataCode->title();
            $systemData->content = (
                $systemData->content == $systemDataCode->defaultContent()
                || $systemData->content == null
            )
                ? $systemDataCode->defaultContent()
                : $systemData->content;
            $systemData->datatype_id = $datatypes->where('name', $systemDataCode->datatype())->value('id');
            $systemData->hint = $systemDataCode->hint();
            $systemData->save();
        }

        // Delete system data codes
        SystemData::query()->whereIn('code', SystemDataCode::obsolete())->delete();
    }
}

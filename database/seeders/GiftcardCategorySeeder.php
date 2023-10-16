<?php

namespace Database\Seeders;

use App\Models\GiftcardCategory;
use Illuminate\Database\Seeder;

class GiftcardCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GiftcardCategory::factory()->count(5)->create();
    }
}

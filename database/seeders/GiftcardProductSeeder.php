<?php

namespace Database\Seeders;

use App\Models\GiftcardProduct;
use Illuminate\Database\Seeder;

class GiftcardProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GiftcardProduct::factory()->create();
    }
}

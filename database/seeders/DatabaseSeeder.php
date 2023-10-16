<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // CountrySeeder::class, // run on first load
            // AdminSeeder::class, // run on first load
            PermissionSeeder::class,
            // BankSeeder::class, // run on first load
            // CurrencySeeder::class, // run on first load
            DatatypeSeeder::class,
            SystemDataSeeder::class,
        ]);
    }
}

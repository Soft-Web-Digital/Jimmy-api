<?php

use App\Enums\SystemDataCode;
use Database\Seeders\DatatypeSeeder;

use function Pest\Laravel\getJson;

uses()->group('api', 'system-data');





it('gets a system data by code', function ($code) {
    test()->seed(DatatypeSeeder::class);
    test()->seed(SystemDataSeeder::class);

    getJson("/api/system-data/{$code}")
        ->assertOk()
        ->assertJsonFragment([
            'code' => $code,
        ]);
})->with(SystemDataCode::values());

<?php

use App\Enums\SystemDataCode;
use App\Models\SystemData;
use Database\Seeders\DatatypeSeeder;
use Database\Seeders\SystemDataSeeder;

uses()->group('database', 'seeder', 'system-data');





it('seeds all the system data into the database', function () {
    test()->seed(DatatypeSeeder::class);

    $count = count(SystemDataCode::cases());

    test()->seed(SystemDataSeeder::class);

    expect(SystemData::count())->toBe($count);
});





it('does not update existing content when run multiple times', function () {
    test()->seed(DatatypeSeeder::class);
    test()->seed(SystemDataSeeder::class);

    $content = '0';

    $systemData = SystemData::query()->where('code', SystemDataCode::GIFTCARD_SELL_SERVICE_CHARGE)->first();
    $systemData->content = $content;
    $systemData->save();

    test()->seed(SystemDataSeeder::class);

    expect($systemData->refresh()->content)->toBe($content);
});

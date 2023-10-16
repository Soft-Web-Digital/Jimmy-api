<?php

use App\Enums\DatatypeEnum;
use App\Models\Datatype;
use Database\Seeders\DatatypeSeeder;

uses()->group('database', 'seeder', 'datatype');





it('seeds all the datatypes into the database', function () {
    $count = count(DatatypeEnum::cases());

    test()->seed(DatatypeSeeder::class);

    expect(Datatype::count())->toBe($count);
});

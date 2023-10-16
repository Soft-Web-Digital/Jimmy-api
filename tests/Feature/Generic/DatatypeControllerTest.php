<?php

use App\Models\Datatype;
use Database\Seeders\DatatypeSeeder;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'datatype');





it('gets all datatypes', function () {
    test()->seed(DatatypeSeeder::class);

    getJson('/api/datatypes')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Datatypes fetched successfully.')
                    ->has(
                        'data.datatypes',
                        Datatype::count(),
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'name',
                                'hint',
                                'developer_hint',
                            ])
                    )
        );
});

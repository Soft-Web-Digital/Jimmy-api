<?php

use App\Models\Banner;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses()->group('api', 'banner');










it('can get the banners', function () {
    Banner::factory()->activated()->count(20)->create();

    getJson('/api/banners')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Banners fetched successfully.')
                    ->has(
                        'data.banners',
                        ($count = Banner::count()) > 10 ? 10 : $count,
                        fn (AssertableJson $json) =>
                            $json->hasAll([
                                'id',
                                'preview_image',
                                'featured_image',
                                'created_at',
                                'updated_at',
                            ])
                    )
        );
});

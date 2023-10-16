<?php

use App\Enums\KycAttribute;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'kyc');




it('can fetch user kyc', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/kyc')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'User KYC fetched successfully')
                    ->etc()
        );
});





it('can initiate a kyc verification', function ($type) {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    postJson("/api/user/kyc/verify/{$type}", [
        'value' => fake()->md5(),
    ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'KYC verification is initiated successfully')
                    ->where('data.kyc', $user->kyc->toArray())
        );
})->with([KycAttribute::BVN->value]);

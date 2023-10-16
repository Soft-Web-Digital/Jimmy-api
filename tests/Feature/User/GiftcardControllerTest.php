<?php

use App\Enums\GiftcardCardType;
use App\Enums\GiftcardStatus;
use App\Enums\GiftcardTradeType;
use App\Models\Giftcard;
use App\Models\GiftcardCategory;
use App\Models\GiftcardProduct;
use App\Models\User;
use App\Models\UserBankAccount;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses()->group('api', 'giftcard');
beforeEach(fn () => test()->refreshDatabase());





it('gets all giftcards in a paginated format', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $giftcardProduct = GiftcardProduct::factory()->create();

    Giftcard::factory()->count(2)->create([
        'giftcard_product_id' => $giftcardProduct->id,
    ]);
    Giftcard::factory()->for($user, 'user')->count(2)->create([
        'giftcard_product_id' => $giftcardProduct->id,
    ]);

    getJson('/api/user/giftcards')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'giftcards' => [
                    'current_page',
                    'data',
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ],
            ],
        ])
        ->assertJsonFragment([
            'user_id' => $user->id,
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcards fetched successfully')
                    ->has('data.giftcards.data', Giftcard::where('user_id', $user->id)->count())
        );
});





it('can get user giftcard stats', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    getJson('/api/user/giftcards/stats')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard stats fetched successfully.')
                    ->whereType('data.stats', 'array')
        );
});





it('can get a giftcard breakdown sale', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $giftcardProduct = GiftcardProduct::factory()
        ->for(GiftcardCategory::factory()->saleActivated())
        ->create([
            'sell_min_amount' => 10,
            'sell_max_amount' => 40,
            'activated_at' => now(),
        ]);

    postJson('/api/user/giftcards/breakdown', [
        'giftcard_product_id' => $giftcardProduct->id,
        'trade_type' => GiftcardTradeType::SELL->value,
        'amount' => rand(10, 40),
    ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'breakdown' => [
                    'rate',
                    'service_charge',
                    'payable_amount',
                ]
            ]
        ])
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard transaction breakdown fetched successfully')
                    ->etc()
        );
});





it('can create a giftcard sale', function ($quantity, $cardType) {
    test()->seed(PermissionSeeder::class);

    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $giftcardProduct = GiftcardProduct::factory()
        ->for(GiftcardCategory::factory()->saleActivated())
        ->create([
            'sell_min_amount' => 10,
            'sell_max_amount' => 40,
            'activated_at' => now(),
        ]);

    Storage::fake();

    $fields = match ($cardType) {
        GiftcardCardType::PHYSICAL->value => [
//            UploadedFile::fake()->image("{$i}.jpg")
            'cards' => array_map(fn ($i) => fake()->imageUrl(), range(1, $quantity)),
            'upload_type' => 'media',
        ],
        GiftcardCardType::VIRTUAL->value => [
            'codes' => array_map(fn ($i) => fake()->md5() . $i, range(1, $quantity)),
            'pins' => array_map(fn ($i) => fake()->md5() . $i, range(1, $quantity)),
            'upload_type' => 'code',
        ],
    };

    $data = array_merge([
        'giftcard_product_id' => $giftcardProduct->id,
        'user_bank_account_id' => UserBankAccount::factory()->for($user)->create()->id,
        'card_type' => $cardType,
        'amount' => rand(10, 40),
        'quantity' => $quantity,
    ], $fields);

    postJson('/api/user/giftcards/sale', $data)
        ->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where(
                        'message',
                        'Giftcard sale ' . Str::of('transaction')->plural($quantity)->toString()
                        . ' created successfully'
                    )
                    ->whereType('data', 'null')
        );
})->with([
    'one' => 1,
    'two' => 2,
])->with(GiftcardCardType::values());





it('can get a single giftcard', function ($id) {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $giftcard = Giftcard::factory()->for($user)->create()->refresh();

    getJson("/api/user/giftcards/{$giftcard->$id}")
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', true)
                    ->where('code', 0)
                    ->where('locale', 'en')
                    ->where('message', 'Giftcard fetched successfully')
                    ->etc()
        );
})->with(['id', 'reference']);





it('can close/delete a giftcard trade', function () {
    $user = User::factory()->create()->refresh();
    sanctumLogin($user, ['*'], 'api_user');

    $giftcard = Giftcard::factory()->for($user)->create(['status' => GiftcardStatus::PENDING])->refresh();

    deleteJson("/api/user/giftcards/{$giftcard->id}")->assertNoContent();
});

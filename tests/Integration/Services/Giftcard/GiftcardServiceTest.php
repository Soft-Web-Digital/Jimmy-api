<?php

use App\DataTransferObjects\GiftcardBreakdownData;
use App\DataTransferObjects\Models\GiftcardModelData;
use App\Enums\GiftcardCardType;
use App\Enums\GiftcardStatus;
use App\Enums\GiftcardTradeType;
use App\Enums\Permission;
use App\Enums\SystemDataCode;
use App\Events\Admin\AdminNotified;
use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Giftcard;
use App\Models\GiftcardProduct;
use App\Models\SystemData;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Notifications\User\GiftcardApprovedNotification;
use App\Notifications\User\GiftcardDeclinedNotification;
use App\Services\Giftcard\GiftcardService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses()->group('service', 'giftcard');
beforeEach(fn () => test()->refreshDatabase());





it('can get the breakdown of a giftcard sale', function () {
    $giftcardProduct = GiftcardProduct::factory()->create()->refresh();

    $modelData = (new GiftcardModelData())
        ->setGiftcardProductId($giftcardProduct->id)
        ->setAmount(fake()->randomFloat())
        ->setTradeType(GiftcardTradeType::SELL);

    $serviceCharge = (float) SystemData::factory()->create([
        'code' => SystemDataCode::GIFTCARD_SELL_SERVICE_CHARGE,
        'content' => 10,
    ])->value('content');
    $rate = $giftcardProduct->sell_rate;
    $totalAmount = $modelData->getAmount() * $rate;

    $breakdown = (new GiftcardService())->breakdown($modelData);

    expect($breakdown)->toBeInstanceOf(GiftcardBreakdownData::class);

    expect($breakdown->getRate())->toBe($rate);
    expect($breakdown->getServiceCharge())->toBe($serviceCharge);
    expect($breakdown->getPayableAmount())->toBe(
        round($totalAmount + ($totalAmount * ($serviceCharge / 100)), 2)
    );
});





it('can create a virtual giftcard sale', function () {
    test()->seed(PermissionSeeder::class);

    Event::fake([AdminNotified::class]);
    Notification::fake();

    $countryId = Country::factory()->create()->id;

    $modelData = (new GiftcardModelData())
        ->setGiftcardProductId(GiftcardProduct::factory()->create(['country_id' => $countryId])->id)
        ->setAmount(fake()->randomFloat())
        ->setTradeType(GiftcardTradeType::SELL)
        ->setCardType(GiftcardCardType::VIRTUAL)
        ->setUploadType('code')
        ->setCodes([fake()->md5()])
        ->setPins([fake()->md5()])
        ->setUserBankAccountId(
            UserBankAccount::factory()
                ->for(User::factory()->state(['country_id' => $countryId]))
                ->create()->id
        );

    (new GiftcardService())->create($modelData);

    Event::assertDispatched(AdminNotified::class);

    Notification::assertCount(Admin::permission(Permission::RECEIVE_NOTIFICATIONS->value)->count());

    test()->assertDatabaseCount('giftcards', 1);
});





it('can create multiple virtual giftcard sales at the same time', function () {
    test()->seed(PermissionSeeder::class);

    Event::fake([AdminNotified::class]);
    Notification::fake();

    $countryId = Country::factory()->create()->id;

    $modelData = (new GiftcardModelData())
        ->setGiftcardProductId(GiftcardProduct::factory()->create(['country_id' => $countryId])->id)
        ->setAmount(fake()->randomFloat())
        ->setTradeType(GiftcardTradeType::SELL)
        ->setCardType(GiftcardCardType::VIRTUAL)
        ->setUploadType('code')
        ->setQuantity(3)
        ->setCodes([fake()->md5(), fake()->md5(), fake()->md5()])
        ->setPins([fake()->md5(), fake()->md5(), fake()->md5()])
        ->setUserBankAccountId(
            UserBankAccount::factory()
                ->for(User::factory()->state(['country_id' => $countryId]))
                ->create()->id
        );

    (new GiftcardService())->create($modelData);

    Event::assertDispatched(AdminNotified::class);

    Notification::assertCount(Admin::permission(Permission::RECEIVE_NOTIFICATIONS->value)->count());

    test()->assertDatabaseCount('giftcards', 3);
});





it('can create a physical giftcard sale', function () {
    test()->seed(PermissionSeeder::class);

    Event::fake([AdminNotified::class]);
    Notification::fake();
    Storage::fake();

    $countryId = Country::factory()->create()->id;

    $modelData = (new GiftcardModelData())
        ->setGiftcardProductId(GiftcardProduct::factory()->create(['country_id' => $countryId])->id)
        ->setAmount(fake()->randomFloat())
        ->setTradeType(GiftcardTradeType::SELL)
        ->setCardType(GiftcardCardType::PHYSICAL)
        ->setUploadType('media')
        ->setCards([
//            UploadedFile::fake()->image('card.jpg'),
            fake()->imageUrl()
        ])
        ->setUserBankAccountId(
            UserBankAccount::factory()
                ->for(User::factory()->state(['country_id' => $countryId]))
                ->create()->id
        );

    (new GiftcardService())->create($modelData);

    Event::assertDispatched(AdminNotified::class);

    test()->assertDatabaseCount('media', count($modelData->getCards()));

    Notification::assertCount(Admin::permission(Permission::RECEIVE_NOTIFICATIONS->value)->count());

    test()->assertDatabaseCount('giftcards', 1);
});





it('can create multiple physical giftcard sales at the same time', function () {
    test()->seed(PermissionSeeder::class);

    Event::fake([AdminNotified::class]);
    Notification::fake();
    Storage::fake();

    $countryId = Country::factory()->create()->id;

    $modelData = (new GiftcardModelData())
        ->setGiftcardProductId(GiftcardProduct::factory()->create(['country_id' => $countryId])->id)
        ->setAmount(fake()->randomFloat())
        ->setTradeType(GiftcardTradeType::SELL)
        ->setCardType(GiftcardCardType::PHYSICAL)
        ->setUploadType('media')
        ->setCards([
//            UploadedFile::fake()->image('card.jpg'),
//            UploadedFile::fake()->image('card.jpg'),
//            UploadedFile::fake()->image('card.jpg'),
            fake()->imageUrl(),
            fake()->imageUrl(),
            fake()->imageUrl(),
        ])
        ->setQuantity(3)
        ->setUserBankAccountId(
            UserBankAccount::factory()
                ->for(User::factory()->state(['country_id' => $countryId]))
                ->create()->id
        );

    (new GiftcardService())->create($modelData);

    Event::assertDispatched(AdminNotified::class);

    test()->assertDatabaseCount('media', count($modelData->getCards()));

    Notification::assertCount(Admin::permission(Permission::RECEIVE_NOTIFICATIONS->value)->count());

    test()->assertDatabaseCount('giftcards', 3);
});





it('can create multiple physical giftcard sales with the group tags', function () {
    test()->seed(PermissionSeeder::class);

    Notification::fake();
    Storage::fake();

    $countryId = Country::factory()->create()->id;

    $modelData = (new GiftcardModelData())
        ->setGiftcardProductId(GiftcardProduct::factory()->create(['country_id' => $countryId])->id)
        ->setAmount(fake()->randomFloat())
        ->setTradeType(GiftcardTradeType::SELL)
        ->setCardType(GiftcardCardType::PHYSICAL)
        ->setUploadType('media')
        ->setGroupTag($groupTag = uniqid('GP'))
        ->setCards([
//            UploadedFile::fake()->image('card.jpg'),
//            UploadedFile::fake()->image('card.jpg'),
//            UploadedFile::fake()->image('card.jpg'),
            fake()->imageUrl(),
            fake()->imageUrl(),
            fake()->imageUrl(),
        ])
        ->setQuantity(3)
        ->setUserBankAccountId(
            UserBankAccount::factory()
                ->for(User::factory()->state(['country_id' => $countryId]))
                ->create()->id
        );

    (new GiftcardService())->create($modelData);

    $giftcards = Giftcard::query()->latest()->limit(3)->get();

    expect($giftcards)->each(fn ($giftcard) => $giftcard->group_tag->toBe(strtoupper($groupTag)));
});





it('can delete a giftcard trade', function ($cardType) {
    Storage::fake();

    $attributes = [
        'trade_type' => GiftcardTradeType::SELL,
        'card_type' => $cardType,
        'status' => GiftcardStatus::PENDING,
    ];

    $giftcard = Giftcard::factory()->create($attributes)->refresh();

    // Confirm card was uploaded
    if ($cardType == GiftcardCardType::PHYSICAL->value) {
        //        $card = UploadedFile::fake()->image('card.jpg');
        //        $giftcard->addMedia($card)->toMediaCollection();
        $giftcard->addMediaFromUrl(fake()->imageUrl())->toMediaCollection();
        test()->assertDatabaseCount('media', 1);
    }

    (new GiftcardService())->delete($giftcard);

    if ($cardType == GiftcardCardType::PHYSICAL->value) {
        test()->assertDatabaseCount('media', 0);
    }

    expect(Giftcard::withTrashed()->find($giftcard->id))
        ->code->toBeNull()
        ->pin->toBeNull()
        ->deleted_at->toBeInstanceOf(\Carbon\Carbon::class);
})->with(GiftcardCardType::values());





it('cannot delete a non-pending giftcard trade', function ($status) {
    $giftcard = Giftcard::factory()->create(['status' => $status])->refresh();

    expect(fn () => (new GiftcardService())->delete($giftcard))
        ->toThrow(NotAllowedException::class, "Giftcard status is currently: {$giftcard->status->value}.");
})->with(array_filter(GiftcardStatus::values(), fn ($status) => $status !== GiftcardStatus::PENDING->value));





it('cannot decline a non-pending giftcard trade', function ($status) {
    $giftcard = Giftcard::factory()->create(['status' => $status])->refresh();

    expect(fn () => (new GiftcardService())->decline($giftcard, Admin::factory()->create()))
        ->toThrow(NotAllowedException::class, "Giftcard status is currently: {$giftcard->status->value}.");
})->with(array_filter(GiftcardStatus::values(), fn ($status) => $status !== GiftcardStatus::PENDING->value));





it('can decline a giftcard sale', function () {
    $giftcard = Giftcard::factory()->create([
        'status' => GiftcardStatus::PENDING,
        'trade_type' => GiftcardTradeType::SELL,
    ])->refresh();

    Notification::fake();

    $proof = [fake()->imageUrl(), fake()->imageUrl(), fake()->imageUrl()];
    $note = fake()->sentence();

    $service = (new GiftcardService())->decline($giftcard, Admin::factory()->create(), $note, $proof);

    Notification::assertSentTo($giftcard->user, GiftcardDeclinedNotification::class);

    expect($service)
        ->toBeInstanceOf(Giftcard::class)
        ->status->toBe(GiftcardStatus::DECLINED)
        ->review_note->toBe($note);
});





it('cannot approve a non-pending giftcard trade', function ($status) {
    $giftcard = Giftcard::factory()->create(['status' => $status])->refresh();

    expect(fn () => (new GiftcardService())->approve($giftcard, Admin::factory()->create(), true))
        ->toThrow(NotAllowedException::class, "Giftcard status is currently: {$giftcard->status->value}.");
})->with(array_filter(GiftcardStatus::values(), fn ($status) => $status !== GiftcardStatus::PENDING->value));





it('can approve a giftcard sale', function () {
    $giftcard = Giftcard::factory()->create([
        'status' => GiftcardStatus::PENDING,
        'trade_type' => GiftcardTradeType::SELL,
    ])->refresh();

    Notification::fake();

    $proof = [fake()->imageUrl(), fake()->imageUrl(), fake()->imageUrl()];
    $note = fake()->sentence();

    $service = (new GiftcardService())->approve(
        giftcard: $giftcard,
        admin: $admin = Admin::factory()->create(),
        completeApproval: true,
        reviewNote: $note,
        reviewProof: $proof
    );

    Notification::assertSentTo($giftcard->user, GiftcardApprovedNotification::class);

    expect($service)
        ->toBeInstanceOf(Giftcard::class)
        ->status->toBe(GiftcardStatus::APPROVED)
        ->review_note->toBe($note)
        ->reviewed_by->toBe($admin->id);
});





it('can partially-approve a giftcard sale', function () {
    $giftcard = Giftcard::factory()->create([
        'status' => GiftcardStatus::PENDING,
        'trade_type' => GiftcardTradeType::SELL,
    ])->refresh();

    Notification::fake();

    $proof = [fake()->imageUrl(), fake()->imageUrl(), fake()->imageUrl()];
    $note = fake()->sentence();
    $reviewAmount = fake()->numberBetween(1000, 9999);

    $service = (new GiftcardService())->approve(
        giftcard: $giftcard,
        admin: $admin = Admin::factory()->create(),
        completeApproval: false,
        reviewAmount: $reviewAmount,
        reviewNote: $note,
        reviewProof: $proof,
    );

    Notification::assertSentTo($giftcard->user, GiftcardApprovedNotification::class);

    expect($service)
        ->toBeInstanceOf(Giftcard::class)
        ->status->toBe(GiftcardStatus::PARTIALLYAPPROVED)
        ->review_note->toBe($note)
        ->reviewed_by->toBe($admin->id);
});





it('can get user giftcard stats', function () {
    $stats = (new GiftcardService())->getStats(User::factory()->create());

    $keys = [
        'total_transactions_count',
        'total_transactions_amount',
    ];

    foreach (GiftcardTradeType::values() as $tradeType) {
        array_push($keys, ...[
            "total_{$tradeType}_count",
            "total_{$tradeType}_amount",
        ]);
    }

    foreach (GiftcardStatus::values() as $status) {
        array_push($keys, ...[
            "total_{$status}_count",
            "total_{$status}_amount",
        ]);

        foreach (GiftcardStatus::values() as $status) {
            array_push($keys, ...[
                "total_{$status}_{$tradeType}_count",
                "total_{$status}_{$tradeType}_amount",
            ]);
        }
    }

    expect($stats[0])->toHaveKeys($keys);
});

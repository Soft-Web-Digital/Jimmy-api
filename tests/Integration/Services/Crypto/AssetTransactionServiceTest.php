<?php

use App\DataTransferObjects\AssetTransactionBreakdownData;
use App\DataTransferObjects\Models\AssetTransactionModelData;
use App\Enums\AssetTransactionStatus;
use App\Enums\AssetTransactionTradeType;
use App\Enums\Permission;
use App\Enums\SystemDataCode;
use App\Events\Admin\AdminNotified;
use App\Models\Admin;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\Network;
use App\Models\SystemData;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Notifications\User\AssetTransactionApprovedNotification;
use App\Notifications\User\AssetTransactionDeclinedNotification;
use App\Services\Crypto\AssetTransactionService;
use Database\Seeders\DatatypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SystemDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses()->group('service', 'asset-transaction');





it('can get a breakdown for an asset transaction', function ($tradeType) {
    $asset = Asset::factory()->create([
        'sell_rate' => 500,
        'buy_rate' => 500,
    ]);

    $serviceCharge = (float) SystemData::factory()->create([
        'code' => match ($tradeType) {
            AssetTransactionTradeType::BUY->value => SystemDataCode::CRYPTO_BUY_SERVICE_CHARGE,
            AssetTransactionTradeType::SELL->value => SystemDataCode::CRYPTO_SELL_SERVICE_CHARGE,
        },
        'content' => 10,
    ])->content;

    $modelData = (new AssetTransactionModelData())
        ->setAssetId($asset->id)
        ->setAssetAmount(fake()->randomFloat(2, 100, 500))
        ->setTradeType($tradeType);

    $rateAttribute = "{$tradeType}_rate";
    $rate = $asset->$rateAttribute;
    $totalAmount = $modelData->getAssetAmount() * $rate;
    $payableAmount = round($totalAmount + ($totalAmount * ($serviceCharge / 100)), 2);

    $breakdown = (new AssetTransactionService())->breakdown($modelData);

    expect($breakdown)->toBeInstanceOf(AssetTransactionBreakdownData::class);

    expect($breakdown->getRate())->toBe($rate);
    expect($breakdown->getServiceCharge())->toBe($serviceCharge);
    expect($breakdown->getPayableAmount())->toBe($payableAmount);
})->with(AssetTransactionTradeType::values());





it('can create a buy asset transaction', function () {
    test()->seed(DatatypeSeeder::class);
    test()->seed(SystemDataSeeder::class);

    $asset = Asset::factory()->create([
        'sell_rate' => 500,
        'buy_rate' => 500,
    ]);
    $network = Network::factory()->create();

    $modelData = (new AssetTransactionModelData())
        ->setNetworkId($network->id)
        ->setAssetId($asset->id)
        ->setAssetAmount(fake()->randomFloat(2, 100, 500))
        ->setTradeType(AssetTransactionTradeType::BUY)
        ->setWalletAddress(fake()->unique()->iban(prefix: '0X'));

    expect((new AssetTransactionService())->create($modelData, User::factory()->create()))
        ->toBeInstanceOf(AssetTransaction::class);
});





it('can create a sell asset transaction', function () {
    test()->seed(DatatypeSeeder::class);
    test()->seed(SystemDataSeeder::class);

    $asset = Asset::factory()->create([
        'sell_rate' => 500,
        'buy_rate' => 500,
    ]);
    $network = Network::factory()->create();
    $user = User::factory()->create();

    $modelData = (new AssetTransactionModelData())
        ->setNetworkId($network->id)
        ->setAssetId($asset->id)
        ->setAssetAmount(fake()->randomFloat(2, 100, 500))
        ->setTradeType(AssetTransactionTradeType::SELL)
        ->setUserBankAccountId(UserBankAccount::factory()->create(['user_id' => $user->id])->id);

    expect((new AssetTransactionService())->create($modelData, $user))
        ->toBeInstanceOf(AssetTransaction::class);
});





it('can mark an asset transaction as transferred', function ($tradeType) {
    test()->seed(PermissionSeeder::class);

    $assetTransaction = AssetTransaction::factory()->create(['trade_type' => $tradeType])->refresh();

    Storage::fake();
    Event::fake();
    Notification::fake();

    $receipt = fake()->imageUrl();
    //    $receipt = UploadedFile::fake()->image('receipt.jpg');

    $transfer = (new AssetTransactionService())->transfer($assetTransaction, $receipt);

    //    Storage::assertExists("receipts/{$receipt->hashName()}");

    Event::assertDispatched(AdminNotified::class);

    Notification::assertCount(Admin::permission(Permission::RECEIVE_NOTIFICATIONS->value)->count());

    expect($transfer)
        ->status->toBe(AssetTransactionStatus::TRANSFERRED)
        ->proof->not->toBeNull();
})->with(AssetTransactionTradeType::values());





it('can decline an asset transaction', function ($tradeType) {
    $assetTransaction = AssetTransaction::factory()->create([
        'trade_type' => $tradeType,
        'status' => AssetTransactionStatus::TRANSFERRED,
    ])->refresh();

    Notification::fake();

    $reviewProof = [fake()->imageUrl(), fake()->imageUrl(), fake()->imageUrl()];
    $note = fake()->text();
    $admin = Admin::factory()->create();

    $decline = (new AssetTransactionService())->decline($assetTransaction, $admin, $note, $reviewProof);

    Notification::assertSentTo($assetTransaction->user, AssetTransactionDeclinedNotification::class);

    expect($decline)
        ->status->toBe(AssetTransactionStatus::DECLINED)
        ->review_note->toBe($note)
        ->review_proof->not->toBeNull()
        ->reviewed_by->toBe($admin->id);
})->with(AssetTransactionTradeType::values());





it('can approve an asset transaction', function ($tradeType) {
    $assetTransaction = AssetTransaction::factory()->create([
        'trade_type' => $tradeType,
        'status' => AssetTransactionStatus::TRANSFERRED,
    ])->refresh();

    Storage::fake();
    Notification::fake();

    $reviewProof = [fake()->imageUrl(), fake()->imageUrl(), fake()->imageUrl()];
    $note = fake()->text();
    $admin = Admin::factory()->create();

    $decline = (new AssetTransactionService())->approve($assetTransaction, $admin, true, null, $note, $reviewProof);

    Notification::assertSentTo($assetTransaction->user, AssetTransactionApprovedNotification::class);

    expect($decline)
        ->status->toBe(AssetTransactionStatus::APPROVED)
        ->review_note->toBe($note)
        ->review_proof->not->toBeNull()
        ->reviewed_by->toBe($admin->id);
})->with(AssetTransactionTradeType::values());





it('can partially-approve an asset transaction', function ($tradeType) {
    $assetTransaction = AssetTransaction::factory()->for(Asset::factory()->create(['buy_rate' => 400]))->create([
        'trade_type' => $tradeType,
        'status' => AssetTransactionStatus::TRANSFERRED,
    ])->refresh();

    Notification::fake();

    $reviewProof = [fake()->imageUrl(), fake()->imageUrl(), fake()->imageUrl()];
    $note = fake()->text();
    $admin = Admin::factory()->create();
    $amount = fake()->numberBetween(1000, 9999);

    $decline = (new AssetTransactionService())->approve($assetTransaction, $admin, false, $amount, $note, $reviewProof);

    Notification::assertSentTo($assetTransaction->user, AssetTransactionApprovedNotification::class);

    expect($decline)
        ->status->toBe(AssetTransactionStatus::PARTIALLYAPPROVED)
        ->review_note->toBe($note)
        ->review_proof->not->toBeNull()
        ->reviewed_by->toBe($admin->id);
})->with(AssetTransactionTradeType::values());





it('can get user asset transaction stats', function () {
    $stats = (new AssetTransactionService())->getStats(User::factory()->create());

    $keys = [
        'total_transactions_count',
        'total_transactions_amount',
    ];

    foreach (AssetTransactionTradeType::values() as $tradeType) {
        array_push($keys, ...[
            "total_{$tradeType}_count",
            "total_{$tradeType}_amount",
        ]);
    }

    foreach (AssetTransactionStatus::values() as $status) {
        array_push($keys, ...[
            "total_{$status}_count",
            "total_{$status}_amount",
        ]);

        foreach (AssetTransactionTradeType::values() as $tradeType) {
            array_push($keys, ...[
                "total_{$status}_{$tradeType}_count",
                "total_{$status}_{$tradeType}_amount",
            ]);
        }
    }

    expect($stats[0])->toHaveKeys($keys);
});

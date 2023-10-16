<?php

use App\DataTransferObjects\WalletData;
use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\User\WalletTransactionUpdateNotification;
use App\Notifications\WalletUpdatedNotification;
use App\Services\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses()->group('service', 'wallet');





it('can deposit into user wallet', function () {
    $user = User::factory()->create();

    $amount = 523.5;

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER);

    (new WalletService())->deposit($user, $walletData);

    $user->refresh();

    expect($user->wallet_balance)->toBe((float) $amount);

    expect($user->walletTransactions()->where('amount', $amount)->exists())->toBeTrue();
});





it('sends a WalletUpdatedNotification on deposits', function () {
    $user = User::factory()->create()->refresh();

    $amount = 5000;

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER);

    Notification::fake();

    (new WalletService())->deposit($user, $walletData);

    Notification::assertSentTo($user, WalletUpdatedNotification::class);
});





it('confirms the wallet transaction is accurate on deposits', function () {
    $user = User::factory()->create()->refresh();

    $amount = 5000;

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER);

    (new WalletService())->deposit($user, $walletData);

    $user->refresh();

    $walletTransaction = WalletTransaction::query()->latest()->first();

    expect($walletTransaction)
        ->user_id->toBe($user->id)
        ->user_type->toBe($user->getMorphClass())
        ->causer_id->toBe($user->id)
        ->causer_type->toBe($user->getMorphClass())
        ->service->toBe($walletData->getWalletServiceType())
        ->type->toBe(WalletTransactionType::CREDIT)
        ->amount->toBe((float) $amount);
});





it('uploads a receipt on deposits', function () {
    $user = User::factory()->create()->refresh();

    $amount = 5000;

    Storage::fake();

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER)
        ->setReceipt($receipt = UploadedFile::fake()->image('receipts/receipt.jpg'));

    (new WalletService())->deposit($user, $walletData);

    Storage::assertExists("receipts/{$receipt->hashName()}");
});





it('can allow any entity to cause a deposit', function ($entity) {
    $user = User::factory()->create();

    $walletData = (new WalletData())
        ->setAmount(2000)
        ->setCauser($entity)
        ->setWalletServiceType(WalletServiceType::OTHER);

    (new WalletService())->deposit($user, $walletData);

    $walletTransaction = WalletTransaction::query()->latest()->first();

    expect($walletTransaction)
        ->causer_id->toBe($entity->id)
        ->causer_type->toBe($entity->getMorphClass());
})->with([
    'admin' => fn () => \App\Models\Admin::factory()->create(),
    'alert' => fn () => \App\Models\Alert::factory()->create(),
]);





it('can withdraw from user wallet', function () {
    $user = User::factory()->create(['wallet_balance' => 5000]);

    $amount = 3000;

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER);

    (new WalletService())->withdraw($user, $walletData);

    $user->refresh();

    expect($user->wallet_balance)->toBe((float) 5000 - 3000);

    expect($user->walletTransactions()->where('amount', $amount)->exists())->toBeTrue();
});





it('sends a WalletUpdatedNotification on withdrawals', function () {
    $user = User::factory()->create(['wallet_balance' => 5000])->refresh();

    $amount = 5000;

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER);

    Notification::fake();

    (new WalletService())->withdraw($user, $walletData);

    Notification::assertSentTo($user, WalletUpdatedNotification::class);
});





it('throws InsufficientFundsException on withdrawals from imbalance wallet', function () {
    $user = User::factory()->create()->refresh();

    $amount = 5000;

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER);

    (new WalletService())->withdraw($user, $walletData);
})->throws(InsufficientFundsException::class);





it('confirms the wallet transaction is accurate on withdrawals', function () {
    $user = User::factory()->create(['wallet_balance' => 10000])->refresh();

    $amount = 5000;

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::TRANSFER);

    (new WalletService())->withdraw($user, $walletData);

    $user->refresh();

    $walletTransaction = WalletTransaction::query()->latest()->first();

    expect($walletTransaction)
        ->user_id->toBe($user->id)
        ->user_type->toBe($user->getMorphClass())
        ->causer_id->toBe($user->id)
        ->causer_type->toBe($user->getMorphClass())
        ->service->toBe($walletData->getWalletServiceType())
        ->type->toBe(WalletTransactionType::DEBIT)
        ->amount->toBe((float) $amount);
});





it('uploads a receipt on withdrawals', function () {
    $user = User::factory()->create(['wallet_balance' => 7000])->refresh();

    $amount = 5000;

    Storage::fake();

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setCauser($user)
        ->setWalletServiceType(WalletServiceType::OTHER)
        ->setReceipt($receipt = UploadedFile::fake()->image('receipts/receipt.jpg'));

    (new WalletService())->withdraw($user, $walletData);

    Storage::assertExists("receipts/{$receipt->hashName()}");
});





it('can allow any entity to cause a withdrawal', function ($entity) {
    $user = User::factory()->create(['wallet_balance' => 2000]);

    $walletData = (new WalletData())
        ->setAmount(2000)
        ->setCauser($entity)
        ->setWalletServiceType(WalletServiceType::OTHER);

    (new WalletService())->withdraw($user, $walletData);

    $walletTransaction = WalletTransaction::query()->latest()->first();

    expect($walletTransaction)
        ->causer_id->toBe($entity->id)
        ->causer_type->toBe($entity->getMorphClass());
})->with([
    'admin' => fn () => \App\Models\Admin::factory()->create(),
    'alert' => fn () => \App\Models\Alert::factory()->create(),
]);





it('can transfer funds between users', function () {
    $users = User::factory()->count(20)->create();

    $amount = 4999;

    $sender = $users->random()->refresh();
    $sender->forceFill(['wallet_balance' => $amount])->save();
    $senderOldBalance = $sender->wallet_balance;

    $receiver = $users->filter(fn ($user) => $user->id != $sender->id)->random()->refresh();
    $receiverOldBalance = $receiver->wallet_balance;

    $walletData = (new WalletData())->setAmount($amount);

    (new WalletService())->transfer($sender, $receiver, $walletData);

    $sender->refresh();
    $receiver->refresh();

    expect($sender->wallet_balance)->toBe((float) $senderOldBalance - $amount);
    expect($receiver->wallet_balance)->toBe((float) $receiverOldBalance + $amount);
});





it('throws InsufficientFundsException for sender during transfer and does not credit/debit the users', function () {
    $users = User::factory()->count(20)->create();

    $amount = 500;

    $sender = $users->random()->refresh();

    $receiver = $users->filter(fn ($user) => $user->id != $sender->id)->random()->refresh();

    $walletData = (new WalletData())->setAmount($amount);

    expect(fn () => (new WalletService())->transfer($sender, $receiver, $walletData))
        ->toThrow(InsufficientFundsException::class);

    $sender->refresh();
    $receiver->refresh();

    expect($sender->wasChanged('wallet_balance'))->toBeFalse();
    expect($receiver->wasChanged('wallet_balance'))->toBeFalse();
});





it('sends a notification to both users involved in transfer', function () {
    $users = User::factory()->count(20)->create();

    $amount = 500;

    $sender = $users->random()->refresh();
    $sender->forceFill(['wallet_balance' => $amount])->save();

    $receiver = $users->filter(fn ($user) => $user->id != $sender->id)->random()->refresh();

    $walletData = (new WalletData())->setAmount($amount);

    Notification::fake();

    (new WalletService())->transfer($sender, $receiver, $walletData);

    Notification::assertSentTo($sender, WalletUpdatedNotification::class);
    Notification::assertSentTo($receiver, WalletUpdatedNotification::class);
});





it('confirms the wallet transactions for both users during transfer', function () {
    $users = User::factory()->count(20)->create();

    $amount = 500;

    $sender = $users->random()->refresh();
    $sender->forceFill(['wallet_balance' => $amount])->save();

    $receiver = $users->filter(fn ($user) => $user->id != $sender->id)->random()->refresh();

    $walletData = (new WalletData())->setAmount($amount);

    (new WalletService())->transfer($sender, $receiver, $walletData);

    /** @var \Illuminate\Database\Eloquent\Collection $walletTransactions */
    $walletTransactions = WalletTransaction::query()->latest()->limit(2)->get();

    expect($walletTransactions)
        ->toHaveCount(2)
        ->each(
            fn ($walletTransaction) => $walletTransaction
                ->service->toBe(WalletServiceType::TRANSFER)
                ->amount->toBe((float) $amount)
                ->receipt->toBe($walletData->getReceipt())
        );

    $senderTransaction = $walletTransactions->filter(
        fn ($walletTransaction) => $walletTransaction->user_id === $sender->id
    )->first();

    expect($senderTransaction)
        ->not->toBeNull()
        ->causer_id->toBe($receiver->id)
        ->causer_type->toBe($receiver->getMorphClass())
        ->type->toBe(WalletTransactionType::DEBIT);

    $receiverTransaction = $walletTransactions->filter(
        fn ($walletTransaction) => $walletTransaction->user_id === $receiver->id
    )->first();

    expect($receiverTransaction)
        ->not->toBeNull()
        ->causer_id->toBe($sender->id)
        ->causer_type->toBe($sender->getMorphClass())
        ->type->toBe(WalletTransactionType::CREDIT);
});





it('throws a NotAllowedException when you transfer to yourself', function () {
    $user = User::factory()->create();

    $amount = 523.5;

    $walletData = (new WalletData())->setAmount($amount);

    (new WalletService())->transfer($user, $user, $walletData);
})->throws(NotAllowedException::class, 'You cannot transfer to yourself.');





it('can upload a receipt used in transfer and share links between them', function () {
    $users = User::factory()->count(20)->create();

    $amount = 500;

    $sender = $users->random()->refresh();
    $sender->forceFill(['wallet_balance' => $amount])->save();

    $receiver = $users->filter(fn ($user) => $user->id != $sender->id)->random()->refresh();

    Storage::fake();

    $walletData = (new WalletData())
        ->setAmount($amount)
        ->setReceipt($receipt = UploadedFile::fake()->image('receipts/receipt.jpg'));

    (new WalletService())->transfer($sender, $receiver, $walletData);

    /** @var \Illuminate\Database\Eloquent\Collection $walletTransactions */
    $walletTransactions = WalletTransaction::query()->latest()->limit(2)->get();

    Storage::assertExists("receipts/{$receipt->hashName()}");

    expect($walletTransactions)
        ->toHaveCount(2)
        ->each(
            fn ($walletTransaction) => $walletTransaction
                ->receipt->toBe($walletData->getReceipt())
        );
});





it('can dynamically finance the user', function ($type) {
    $amount = 5000;

    $attributes = match ($type) {
        WalletTransactionType::DEBIT->value => ['wallet_balance' => $amount * 2],
        default => []
    };

    $user = User::factory()->create($attributes)->refresh();

    $data = (new WalletService())->finance($user, WalletTransactionType::from($type), $user, $amount);

    expect($data)->toBeInstanceOf(User::class);

    expect($data)
        ->wallet_balance->toBe($type === WalletTransactionType::CREDIT->value ? (float) $amount : (float) $amount);
})->with(WalletTransactionType::values());





it('verifies amount is withdrawable', function () {
    $user = User::factory()->create(['wallet_balance' => 1000])->refresh();

    $walletService = new WalletService();

    expect($walletService->verifyWithdrawable($user, 1000))->toBeTrue();
    expect($walletService->verifyWithdrawable($user, 500))->toBeTrue();
    expect($walletService->verifyWithdrawable($user, 200))->toBeTrue();
    expect($walletService->verifyWithdrawable($user, 1200))->toBeFalse();
    expect($walletService->verifyWithdrawable($user, 1001))->toBeFalse();
    expect($walletService->verifyWithdrawable($user, 1000.1))->toBeFalse();
});





it('can validate a wallet transaction to cancel on insufficient balance', function () {
    $walletTransaction = WalletTransaction::factory()
        ->for(User::factory(), 'user')
        ->create([
            'status' => WalletTransactionStatus::PENDING,
            'amount' => 1000,
        ]);

    expect((new WalletService())->validate($walletTransaction->id, ['user']))
        ->toBeInstanceOf(WalletTransaction::class)
        ->status->toBe(WalletTransactionStatus::CANCELLED);
});





it('can decline a wallet transaction', function () {
    $walletTransaction = WalletTransaction::factory()
        ->for(User::factory(), 'user')
        ->create([
            'status' => WalletTransactionStatus::PENDING,
            'amount' => 1000,
        ]);

    $note = fake()->sentence();

    Notification::fake();

    (new WalletService())->decline($walletTransaction, $note);

    expect($walletTransaction)
        ->status->toBe(WalletTransactionStatus::DECLINED)
        ->admin_note->toBe($note);

    Notification::assertSentTo($walletTransaction->user, WalletTransactionUpdateNotification::class);
});





it('can approve a wallet transaction', function () {
    $walletTransaction = WalletTransaction::factory()
        ->for(User::factory()->create(['wallet_balance' => 5000]), 'user')
        ->create([
            'status' => WalletTransactionStatus::PENDING,
            'amount' => 1000,
        ]);

    Notification::fake();
    Storage::fake();

    $note = fake()->sentence();
    $receipt = UploadedFile::fake()->image('receipt.jpg');

    (new WalletService())->approve(Admin::factory()->create(), $walletTransaction, $note, $receipt);

    expect($walletTransaction)
        ->status->toBe(WalletTransactionStatus::COMPLETED)
        ->admin_note->toBe($note)
        ->receipt->not->toBeNull();

    Storage::assertExists("receipts/{$receipt->hashName()}");

    Notification::assertSentTo($walletTransaction->user, WalletTransactionUpdateNotification::class);

    expect($walletTransaction->user->wallet_balance)->toBe((float) (5000 - 1000));
});





it('can reject an approval of a wallet transaction due to insufficient wallet balance', function () {
    $walletTransaction = WalletTransaction::factory()
        ->for(User::factory()->create(), 'user')
        ->create([
            'status' => WalletTransactionStatus::PENDING,
            'amount' => 1000,
        ]);

    (new WalletService())->approve(Admin::factory()->create(), $walletTransaction);
})->throws(InsufficientFundsException::class);

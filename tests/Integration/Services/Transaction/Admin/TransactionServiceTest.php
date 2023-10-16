<?php

use App\DataTransferObjects\TransactionFilterData;
use App\Enums\AssetTransactionStatus;
use App\Enums\GiftcardStatus;
use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Models\AssetTransaction;
use App\Models\Giftcard;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Transaction\Admin\TransactionService;

uses()->group('service', 'transaction');





it('can get giftcard stats', function ($user) {
    Giftcard::factory()->count(1)->create();

    $attributes = match ($user) {
        null => [],
        default => ['user_id' => $user->id]
    };

    Giftcard::factory()->count(4)->create($attributes);

    $transactionFilterData = (new TransactionFilterData())->setUserId($user?->id);

    $data = (new TransactionService())->giftcardStats($transactionFilterData)->get();

    $keys = [
        'total_transactions_count',
    ];
    foreach (GiftcardStatus::values() as $status) {
        $keys[] = "total_{$status}_transactions_count";
        $keys[] = "total_{$status}_transactions_amount";
    }

    expect($data->first())->toHaveKeys($keys);
})->with([
    'null' => fn () => null,
    'user' => fn () => User::factory()->create(),
]);





it('can get asset transaction stats', function ($user) {
    AssetTransaction::factory()->count(1)->create();

    $attributes = match ($user) {
        null => [],
        default => ['user_id' => $user->id]
    };

    AssetTransaction::factory()->count(4)->create($attributes);

    $transactionFilterData = (new TransactionFilterData())->setUserId($user?->id);

    $data = (new TransactionService())->assetTransactionStats($transactionFilterData)->get();

    $keys = [
        'total_transactions_count',
    ];
    foreach (AssetTransactionStatus::values() as $status) {
        $keys[] = "total_{$status}_transactions_count";
        $keys[] = "total_{$status}_transactions_amount";
    }

    expect($data->first())->toHaveKeys($keys);
})->with([
    'null' => fn () => null,
    'user' => fn () => User::factory()->create(),
]);





it('can get wallet transaction stats', function ($user) {
    WalletTransaction::factory()->count(1)->create();

    $attributes = match ($user) {
        null => [],
        default => ['user_id' => $user->id]
    };

    WalletTransaction::factory()->count(4)->create($attributes);

    $transactionFilterData = (new TransactionFilterData())->setUserId($user?->id);

    $data = (new TransactionService())->walletTransactionStats($transactionFilterData)->get();

    $keys = [
        'total_transactions_count',
    ];
    foreach (WalletTransactionStatus::values() as $status) {
        $keys[] = "total_{$status}_transactions_count";
        $keys[] = "total_{$status}_transactions_amount";

        foreach (WalletServiceType::values() as $service) {
            $keys[] = "total_{$status}_{$service}_transactions_count";
            $keys[] = "total_{$status}_{$service}_transactions_amount";
        }
    }
    foreach (WalletServiceType::values() as $service) {
        $keys[] = "total_{$service}_transactions_count";
        $keys[] = "total_{$service}_transactions_amount";
    }

    expect($data->first())->toHaveKeys($keys);
})->with([
    'null' => fn () => null,
    'user' => fn () => User::factory()->create(),
]);

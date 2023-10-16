<?php

use App\DataTransferObjects\TransactionFilterData;
use App\Models\AssetTransaction;
use App\Models\Giftcard;
use App\Models\User;
use App\Services\Transaction\User\TransactionService;

uses()->group('service', 'transaction');





it('can get giftcard records', function ($user) {
    Giftcard::factory()->count(1)->create();

    $attributes = match ($user) {
        null => [],
        default => ['user_id' => $user->id]
    };

    Giftcard::factory()->count(4)->create($attributes);

    $transactionFilterData = (new TransactionFilterData())->setUserId($user?->id);

    $data = (new TransactionService())->giftcardRecords($transactionFilterData)->get();

    expect($data->first())->toHaveKeys([
        'id',
        'type',
        'reference',
        'status',
        'trade_type',
        'amount',
        'payable_amount',
        'rate',
        'review_rate',
        'service_charge',
        'category_name',
        'category_icon',
    ]);

    expect($data->count())->toBe($user ? 4 : 5);
})->with([
    'null' => fn () => null,
    'user' => fn () => User::factory()->create(),
]);





it('can get giftcard stats', function ($user) {
    Giftcard::factory()->count(1)->create();

    $attributes = match ($user) {
        null => [],
        default => ['user_id' => $user->id]
    };

    Giftcard::factory()->count(4)->create($attributes);

    $transactionFilterData = (new TransactionFilterData())->setUserId($user?->id);

    $data = (new TransactionService())->giftcardStats($transactionFilterData)->get();

    expect($data->first())->toHaveKeys([
        'type',
        'total_transactions_count',
        'total_transactions_amount',
    ]);
})->with([
    'null' => fn () => null,
    'user' => fn () => User::factory()->create(),
]);





it('can get asset transaction records', function ($user) {
    AssetTransaction::factory()->count(1)->create();

    $attributes = match ($user) {
        null => [],
        default => ['user_id' => $user->id]
    };

    AssetTransaction::factory()->count(4)->create($attributes);

    $transactionFilterData = (new TransactionFilterData())->setUserId($user?->id);

    $data = (new TransactionService())->assetTransactionRecords($transactionFilterData)->get();

    expect($data->first())->toHaveKeys([
        'id',
        'type',
        'reference',
        'status',
        'trade_type',
        'amount',
        'payable_amount',
        'rate',
        'review_rate',
        'service_charge',
        'category_name',
        'category_icon',
    ]);

    expect($data->count())->toBe($user ? 4 : 5);
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

    expect($data->first())->toHaveKeys([
        'type',
        'total_transactions_count',
        'total_transactions_amount',
    ]);
})->with([
    'null' => fn () => null,
    'user' => fn () => User::factory()->create(),
]);

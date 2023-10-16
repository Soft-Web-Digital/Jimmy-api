<?php

use App\DataTransferObjects\Models\UserBankAccountModelData;
use App\Models\Bank;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Services\Profile\User\UserBankAccountService;

uses()->group('service', 'external', 'bank-account');





it('can verify a NG bank account', function ($data) {
    ['bankId' => $bankId, 'accountNumber' => $accountNumber] = $data;

    expect((new UserBankAccountService())->verify($bankId, $accountNumber))
        ->toBeInstanceOf(UserBankAccountModelData::class);
})->with([
    'zenith' => fn () => [
        'bankId' => Bank::factory()->create(['code' => '057'])->id,
        'accountNumber' => '0000000000',
    ],
]);





it('can create a NG bank account', function ($data) {
    $user = User::factory()->create();

    ['bankId' => $bankId, 'accountNumber' => $accountNumber] = $data;

    expect((new UserBankAccountService())->store($user, $bankId, $accountNumber))
        ->toBeInstanceOf(UserBankAccount::class);

    expect($user->bankAccounts()->count())->toBe(1);
})->with([
    'zenith' => fn () => [
        'bankId' => Bank::factory()->create(['code' => '057'])->id,
        'accountNumber' => '0000000000',
    ],
]);

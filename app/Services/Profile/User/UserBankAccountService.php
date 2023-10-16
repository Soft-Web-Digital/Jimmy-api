<?php

declare(strict_types=1);

namespace App\Services\Profile\User;

use App\Contracts\CanVerifyBankAccount;
use App\DataTransferObjects\Models\UserBankAccountModelData;
use App\Models\Bank;
use App\Models\User;
use App\Models\UserBankAccount;
use Illuminate\Support\Facades\App;

class UserBankAccountService
{
    /**
     * Verify the bank account.
     *
     * @param string $bankId
     * @param string $accountNumber
     * @return \App\DataTransferObjects\Models\UserBankAccountModelData
     */
    public function verify(string $bankId, string $accountNumber): UserBankAccountModelData
    {
        /** @var \App\Contracts\CanVerifyBankAccount $service */
        $service = App::make(CanVerifyBankAccount::class);

        $bank = Bank::query()->findOrFail($bankId);

        return $service->verify($bank, $accountNumber);
    }

    /**
     * Create a bank account.
     *
     * @param \App\Models\User $user
     * @param string $bankId
     * @param string $accountNumber
     * @return \App\Models\UserBankAccount
     */
    public function store(User $user, string $bankId, string $accountNumber): UserBankAccount
    {
        $userBankAccountModelData = $this->verify($bankId, $accountNumber);

        return UserBankAccount::query()->create([
            'bank_id' => $userBankAccountModelData->getBank()->id,
            'user_id' => $user->id,
            'account_number' => $userBankAccountModelData->getAccountNumber(),
            'account_name' => $userBankAccountModelData->getAccountName(),
        ])->refresh();
    }
}

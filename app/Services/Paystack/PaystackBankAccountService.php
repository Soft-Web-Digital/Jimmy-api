<?php

declare(strict_types=1);

namespace App\Services\Paystack;

use App\Contracts\CanVerifyBankAccount;
use App\DataTransferObjects\Models\UserBankAccountModelData;
use App\Exceptions\ExpectationFailedException;
use App\Models\Bank;

class PaystackBankAccountService extends PaystackService implements CanVerifyBankAccount
{
    /**
     * Verify the bank account.
     *
     * @param \App\Models\Bank $bank
     * @param string $accountNumber
     * @return \App\DataTransferObjects\Models\UserBankAccountModelData
     */
    public function verify(Bank $bank, string $accountNumber): UserBankAccountModelData
    {
        $bankAccount = $this->getFactory()->bank->resolve([
            'account_number' => $accountNumber,
            'bank_code' => $bank->code,
        ]);

        if (!$bankAccount->status) {
            throw new ExpectationFailedException('The account number could not be resolved');
        }

        return (new UserBankAccountModelData())
            ->setBank($bank)
            ->setAccountName($bankAccount->data->account_name)
            ->setAccountNumber($bankAccount->data->account_number);
    }
}

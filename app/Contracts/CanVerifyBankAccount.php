<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\Models\UserBankAccountModelData;
use App\Models\Bank;

interface CanVerifyBankAccount
{
    /**
     * Verify the bank account.
     *
     * @param \App\Models\Bank $bank
     * @param string $accountNumber
     * @return \App\DataTransferObjects\Models\UserBankAccountModelData
     */
    public function verify(Bank $bank, string $accountNumber): UserBankAccountModelData;
}

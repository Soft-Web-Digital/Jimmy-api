<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use App\Models\Bank;

class UserBankAccountModelData
{
    /**
     * The bank.
     *
     * @var \App\Models\Bank
     */
    private Bank $bank;

    /**
     * The account number.
     *
     * @var string
     */
    private string $accountNumber;

    /**
     * The account name.
     *
     * @var string
     */
    private string $accountName;

    /**
     * Get the bank.
     *
     * @return \App\Models\Bank
     */
    public function getBank(): Bank
    {
        return $this->bank;
    }

    /**
     * Set the bank.
     *
     * @param \App\Models\Bank $bank The bank.
     *
     * @return self
     */
    public function setBank(Bank $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * Get the account number.
     *
     * @return string
     */
    public function getAccountNumber(): string
    {
        return $this->accountNumber;
    }

    /**
     * Set the account number.
     *
     * @param string $accountNumber The account number.
     *
     * @return self
     */
    public function setAccountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * Get the account name.
     *
     * @return string
     */
    public function getAccountName(): string
    {
        return $this->accountName;
    }

    /**
     * Set the account name.
     *
     * @param string $accountName The account name.
     *
     * @return self
     */
    public function setAccountName(string $accountName): self
    {
        $this->accountName = $accountName;

        return $this;
    }

    /**
     * Serialize to array
     *
     * @return array<string, array<int, string>|string>
     */
    public function toArray(): array
    {
        return [
            'bank' => $this->getBank()->only([
                'id',
                'name',
            ]),
            'account_number' => $this->getAccountNumber(),
            'account_name' => $this->getAccountName(),
        ];
    }
}

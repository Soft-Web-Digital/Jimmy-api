<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\WalletData;
use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;

/**
 * @property float $wallet_balance
 * @property-read string $fullName
 * @method void notify($instance)
 */
interface HasWallet
{
    /**
     * Deposit amount into wallet.
     *
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return void
     */
    public function deposit(WalletData $walletData): void;

    /**
     * Withdraw amount from wallet.
     *
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return void
     */
    public function withdraw(WalletData $walletData): void;

    /**
     * Record the wallet transaction and send a notification.
     *
     * @param \App\DataTransferObjects\WalletData $walletData
     * @param \App\Enums\WalletTransactionType $walletTransactionType
     * @return \App\Models\WalletTransaction
     */
    public function recordWalletTransaction(
        WalletData $walletData,
        WalletTransactionType $walletTransactionType
    ): WalletTransaction;

    /**
     * Get the wallet transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function walletTransactions(): \Illuminate\Database\Eloquent\Relations\MorphMany;
}

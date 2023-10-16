<?php

declare(strict_types=1);

namespace App\Traits;

use App\DataTransferObjects\WalletData;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Models\WalletTransaction;
use App\Notifications\WalletUpdatedNotification;
use Illuminate\Support\Str;

trait WalletTrait
{
    /**
     * Deposit amount into wallet.
     *
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return void
     */
    public function deposit(WalletData $walletData): void
    {
        $this->wallet_balance += $walletData->getAmount();
        $this->save();

        $this->recordWalletTransaction($walletData, WalletTransactionType::CREDIT);
    }

    /**
     * Withdraw amount from wallet.
     *
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return void
     */
    public function withdraw(WalletData $walletData): void
    {
        throw_if(
            $this->wallet_balance - $walletData->getAmount() < 0,
            InsufficientFundsException::class,
            'Account has insufficient funds.'
        );

        $this->wallet_balance -= $walletData->getAmount();
        $this->save();

        $this->recordWalletTransaction($walletData, WalletTransactionType::DEBIT);
    }

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
    ): WalletTransaction {
        $status = $walletData->getWalletTransactionStatus();

        $summary = (
            'NGN '
            . number_format($walletData->getAmount(), 2)
            . ($status && $status == WalletTransactionStatus::PENDING ? ' is to be ' : ' was ')
            . $walletTransactionType->sentenceTerm()
            . ' your wallet. Triggered by '
            . (
                $this->is($walletData->getCauser())
                    ? 'you'
                    : (
                        Str::of($walletData->getCauser()->getMorphClass())->headline()->lower()
                        . ' ('
                        . (
                            $walletData->getCauser()->reference ??
                            $walletData->getCauser()->full_name ??
                            $walletData->getCauser()->id
                        )
                        . ')'
                    )
            )
        );

        if ($walletTransaction = $walletData->getWalletTransaction()) {
            $walletTransaction->updateOrFail([
                'summary' => $summary,
                'status' => $status,
                'admin_note' => $walletData->getAdminNote(),
                'receipt' => $walletData->getReceipt(),
            ]);
        } else {
            /** @var \App\Models\WalletTransaction $walletTransaction */
            $walletTransaction = $this->walletTransactions()->create([
                'causer_id' => $walletData->getCauser()->id,
                'causer_type' => $walletData->getCauser()->getMorphClass(),
                'bank_id' => $walletData->getBankId(),
                'account_name' => $walletData->getAccountName(),
                'account_number' => $walletData->getAccountNumber(),
                'service' => $walletData->getWalletServiceType(),
                'type' => $walletTransactionType,
                'status' => $status ?? WalletTransactionStatus::COMPLETED,
                'amount' => $walletData->getAmount(),
                'summary' => $summary,
                'admin_note' => $walletData->getAdminNote(),
                'receipt' => $walletData->getReceipt(),
                'comment' => $walletData->getComment(),
            ]);
        }

        $this->notify(new WalletUpdatedNotification($summary, $walletData->getAdminNote()));

        return $walletTransaction->refresh();
    }

    /**
     * Get the wallet transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function walletTransactions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'user');
    }
}

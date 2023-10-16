<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\HasWallet;
use App\DataTransferObjects\WalletData;
use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Events\Admin\AdminNotified;
use App\Exceptions\NotAllowedException;
use App\Models\UserBankAccount;
use App\Models\WalletTransaction;
use App\Notifications\Admin\WalletWithdrawalRequestNotification;
use App\Notifications\User\WalletTransactionUpdateNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Finance (deposit or withdraw) the user.
     *
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user
     * @param \App\Enums\WalletTransactionType $type
     * @param \Illuminate\Database\Eloquent\Model $causer
     * @param float $amount
     * @param string|null $note
     * @return \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model
     */
    public function finance(
        HasWallet&Model $user,
        WalletTransactionType $type,
        Model $causer,
        float $amount,
        ?string $note = null,
        ?UploadedFile $receipt = null
    ): HasWallet&Model {
        $walletData = (new WalletData())
            ->setAmount($amount)
            ->setCauser($causer)
            ->setWalletServiceType(WalletServiceType::OTHER)
            ->setAdminNote($note)
            ->setReceipt($receipt);

        switch ($type) {
            case WalletTransactionType::CREDIT:
                $this->deposit($user, $walletData);
                break;

            default:
                $this->withdraw($user, $walletData);
                break;
        }

        return $user->refresh();
    }

    /**
     * Deposit amount into wallet.
     *
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return void
     */
    public function deposit(HasWallet&Model $user, WalletData $walletData): void
    {
        DB::beginTransaction();

        try {
            /** @var \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user */
            $user = $user->query()->lockForUpdate()->findOrFail($user->id);

            $user->deposit($walletData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            if ($walletData->getReceipt()) {
                Storage::delete(Str::after($walletData->getReceipt(), config('app.url')));
            }

            throw $e;
        }
    }

    /**
     * Withdraw amount from wallet.
     *
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return void
     */
    public function withdraw(HasWallet&Model $user, WalletData $walletData): void
    {
        DB::beginTransaction();

        try {
            /** @var \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user */
            $user = $user->query()->lockForUpdate()->findOrFail($user->id);

            $user->withdraw($walletData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            if ($walletData->getReceipt()) {
                Storage::delete(Str::after($walletData->getReceipt(), config('app.url')));
            }

            throw $e;
        }
    }

    /**
     * Transfer funds between users.
     *
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $sender
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $receiver
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return void
     */
    public function transfer(HasWallet&Model $sender, HasWallet&Model $receiver, WalletData $walletData): void
    {
        // Modify the wallet data
        $walletData->setWalletServiceType(WalletServiceType::TRANSFER);

        throw_if($receiver->id == $sender->id, NotAllowedException::class, 'You cannot transfer to yourself.');

        DB::beginTransaction();

        try {
            /** @var \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $sender */
            $sender = $sender->query()->lockForUpdate()->findOrFail($sender->id);
            $sender->withdraw($walletData->setCauser($receiver));

            /** @var \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $receiver */
            $receiver = $receiver->query()->lockForUpdate()->findOrFail($receiver->id);
            $receiver->deposit($walletData->setCauser($sender));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            if ($walletData->getReceipt()) {
                Storage::delete(Str::after($walletData->getReceipt(), config('app.url')));
            }

            throw $e;
        }
    }

    /**
     * Verify the amount is withdrawable.
     *
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user
     * @param float $amount
     * @return bool
     */
    public function verifyWithdrawable(HasWallet&Model $user, float $amount): bool
    {
        return $user->wallet_balance - $amount >= 0;
    }

    /**
     * Request a bank withdrawal.
     *
     * @param \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user
     * @param \App\DataTransferObjects\WalletData $walletData
     * @return \App\Models\WalletTransaction
     */
    public function requestWithdrawal(HasWallet&Model $user, WalletData $walletData): WalletTransaction
    {
        /** @var \App\Models\UserBankAccount $userBankAccount */
        $userBankAccount = UserBankAccount::query()->findOrFail($walletData->getUserBankAccountId());

        // Modify the wallet data
        $walletData->setWalletServiceType(WalletServiceType::WITHDRAWAL)
            ->setBankId($userBankAccount->bank_id)
            ->setAccountName($userBankAccount->account_name)
            ->setAccountNumber($userBankAccount->account_number)
            ->setWalletTransactionStatus(WalletTransactionStatus::PENDING);

        $walletTransaction = $user->recordWalletTransaction($walletData, WalletTransactionType::DEBIT);

        event(new AdminNotified(new WalletWithdrawalRequestNotification($user, $walletData->getAmount())));

        return $walletTransaction;
    }

    /**
     * Validate the wallet transaction.
     *
     * @param string $walletTransaction
     * @param array<int, mixed> $relations
     * @return \App\Models\WalletTransaction
     */
    public function validate(string $walletTransaction, array $relations = []): WalletTransaction
    {
        /** @var \App\Models\WalletTransaction $walletTransaction */
        $walletTransaction = WalletTransaction::query()->with($relations)->findOrFail($walletTransaction);

        $user = $walletTransaction->user;

        // Cancel the wallet transaction if it is pending & no longer withdrawable
        if (
            $walletTransaction->status === WalletTransactionStatus::PENDING
            && !(new WalletService())->verifyWithdrawable($user, $walletTransaction->amount)
        ) {
            $walletTransaction->cancel();
            $walletTransaction->refresh();
        }

        return $walletTransaction;
    }

    /**
     * Decline the wallet transaction.
     *
     * @param \App\Models\WalletTransaction $walletTransaction
     * @param string|null $note
     * @return \App\Models\WalletTransaction
     */
    public function decline(WalletTransaction $walletTransaction, string|null $note = null): WalletTransaction
    {
        $walletTransaction->decline($note);

        /** @var \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user */
        $user = $walletTransaction->user;
        $user->notify(new WalletTransactionUpdateNotification(
            'Wallet Transaction Declined',
            "Your wallet {$walletTransaction->service->value} of "
                . "NGN {$walletTransaction->amount} has been declined."
        ));

        return $walletTransaction->refresh();
    }

    /**
     * Approve the wallet transaction.
     *
     * @param \Illuminate\Database\Eloquent\Model $causer
     * @param \App\Models\WalletTransaction $walletTransaction
     * @param string|null $adminNote
     * @param \Illuminate\Http\UploadedFile|null $receipt
     * @return \App\Models\WalletTransaction
     */
    public function approve(
        Model $causer,
        WalletTransaction $walletTransaction,
        ?string $adminNote = null,
        ?UploadedFile $receipt = null
    ): WalletTransaction {
        $walletData = (new WalletData())
            ->setWalletTransaction($walletTransaction)
            ->setWalletTransactionStatus(WalletTransactionStatus::COMPLETED)
            ->setAdminNote($adminNote)
            ->setReceipt($receipt)
            ->setAmount($walletTransaction->amount)
            ->setCauser($causer);

        DB::beginTransaction();

        try {
            $this->withdraw($walletTransaction->user, $walletData);

            /** @var \App\Contracts\HasWallet&\Illuminate\Database\Eloquent\Model $user */
            $user = $walletTransaction->user;
            $user->notify(new WalletTransactionUpdateNotification(
                'Wallet Transaction Approved',
                "Your wallet {$walletTransaction->service->value} of "
                    . "NGN {$walletTransaction->amount} has been approved."
            ));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $walletTransaction->refresh();
    }
}

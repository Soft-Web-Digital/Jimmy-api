<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Exceptions\ExpectationFailedException;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WalletData
{
    /**
     * The amount.
     *
     * @var float
     */
    private float $amount;

    /**
     * The causer.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    private Model $causer;

    /**
     * The wallet service type.
     *
     * @var \App\Enums\WalletServiceType
     */
    private WalletServiceType $walletServiceType;

    /**
     * The admin note.
     *
     * @var string|null
     */
    private string|null $adminNote = null;

    /**
     * The receipt.
     *
     * @var string|null
     */
    private string|null $receipt = null;

    /**
     * The status.
     *
     * @var \App\Enums\WalletTransactionStatus|null
     */
    private WalletTransactionStatus|null $walletTransactionStatus = null;

    /**
     * The user bank account ID.
     *
     * @var string|null
     */
    private string|null $userBankAccountId = null;

    /**
     * The bank ID.
     *
     * @var string|null
     */
    private string|null $bankId = null;

    /**
     * The account name.
     *
     * @var string|null
     */
    private string|null $accountName = null;

    /**
     * The account number.
     *
     * @var string|null
     */
    private string|null $accountNumber = null;

    /**
     * The comment.
     *
     * @var string|null
     */
    private string|null $comment = null;

    /**
     * The wallet transaction.
     *
     * @var \App\Models\WalletTransaction|null
     */
    private WalletTransaction|null $walletTransaction = null;

    /**
     * Get the amount.
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Set the amount.
     *
     * @param float $amount The amount.
     *
     * @return self
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get the causer.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getCauser(): \Illuminate\Database\Eloquent\Model
    {
        return $this->causer;
    }

    /**
     * Set the causer.
     *
     * @param \Illuminate\Database\Eloquent\Model $causer The causer.
     *
     * @return self
     */
    public function setCauser(Model $causer): self
    {
        $this->causer = $causer;

        return $this;
    }

    /**
     * Get the wallet service type.
     *
     * @return \App\Enums\WalletServiceType
     */
    public function getWalletServiceType(): \App\Enums\WalletServiceType
    {
        return $this->walletServiceType;
    }

    /**
     * Set the wallet service type.
     *
     * @param \App\Enums\WalletServiceType $walletServiceType The wallet service type.
     *
     * @return self
     */
    public function setWalletServiceType(WalletServiceType $walletServiceType): self
    {
        $this->walletServiceType = $walletServiceType;

        return $this;
    }

    /**
     * Get the admin note.
     *
     * @return string|null
     */
    public function getAdminNote(): string|null
    {
        return $this->adminNote;
    }

    /**
     * Set the admin note.
     *
     * @param string|null $adminNote The admin note.
     *
     * @return self
     */
    public function setAdminNote($adminNote): self
    {
        $this->adminNote = $adminNote;

        return $this;
    }

    /**
     * Get the receipt.
     *
     * @return string|null
     */
    public function getReceipt(): string|null
    {
        return $this->receipt;
    }

    /**
     * Set the receipt.
     *
     * @param \Illuminate\Http\UploadedFile|null $receipt The receipt.
     *
     * @return self
     */
    public function setReceipt($receipt): self
    {
        if ($receipt instanceof UploadedFile) {
            $path = $receipt->store('receipts');

            throw_if($path === false, ExpectationFailedException::class, 'Receipt could not be uploaded');

            $this->receipt = Storage::url($path);
        }

        return $this;
    }

    /**
     * Get the user bank account ID.
     *
     * @return string|null
     */
    public function getUserBankAccountId(): string|null
    {
        return $this->userBankAccountId;
    }

    /**
     * Set the user bank account ID.
     *
     * @param string|null $userBankAccountId The user bank account ID.
     *
     * @return self
     */
    public function setUserBankAccountId($userBankAccountId): self
    {
        $this->userBankAccountId = $userBankAccountId;

        return $this;
    }

    /**
     * Get the comment.
     *
     * @return string|null
     */
    public function getComment(): string|null
    {
        return $this->comment;
    }

    /**
     * Set the comment.
     *
     * @param string|null $comment The comment.
     *
     * @return self
     */
    public function setComment($comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get the bank ID.
     *
     * @return string|null
     */
    public function getBankId(): string|null
    {
        return $this->bankId;
    }

    /**
     * Set the bank ID.
     *
     * @param string|null $bankId The bank ID.
     *
     * @return self
     */
    public function setBankId($bankId): self
    {
        $this->bankId = $bankId;

        return $this;
    }

    /**
     * Get the account name.
     *
     * @return string|null
     */
    public function getAccountName(): string|null
    {
        return $this->accountName;
    }

    /**
     * Set the account name.
     *
     * @param string|null $accountName The account name.
     *
     * @return self
     */
    public function setAccountName($accountName): self
    {
        $this->accountName = $accountName;

        return $this;
    }

    /**
     * Get the account number.
     *
     * @return string|null
     */
    public function getAccountNumber(): string|null
    {
        return $this->accountNumber;
    }

    /**
     * Set the account number.
     *
     * @param string|null $accountNumber The account number.
     *
     * @return self
     */
    public function setAccountNumber($accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * Get the status.
     *
     * @return \App\Enums\WalletTransactionStatus|null
     */
    public function getWalletTransactionStatus(): \App\Enums\WalletTransactionStatus|null
    {
        return $this->walletTransactionStatus;
    }

    /**
     * Set the status.
     *
     * @param \App\Enums\WalletTransactionStatus|null $walletTransactionStatus The status.
     *
     * @return self
     */
    public function setWalletTransactionStatus($walletTransactionStatus): self
    {
        $this->walletTransactionStatus = $walletTransactionStatus;

        return $this;
    }

    /**
     * Get the wallet transaction.
     *
     * @return \App\Models\WalletTransaction|null
     */
    public function getWalletTransaction(): \App\Models\WalletTransaction|null
    {
        return $this->walletTransaction;
    }

    /**
     * Set the wallet transaction.
     *
     * @param \App\Models\WalletTransaction|null $walletTransaction The wallet transaction.
     *
     * @return self
     */
    public function setWalletTransaction($walletTransaction): self
    {
        $this->walletTransaction = $walletTransaction;

        return $this;
    }
}

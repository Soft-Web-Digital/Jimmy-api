<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

use App\DataTransferObjects\Models\TransactionPinResetCodeModelData;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface HasTransactionPin
{
    /**
     * Generate code for resetting transaction pin.
     *
     * @return \App\DataTransferObjects\Models\TransactionPinResetCodeModelData
     */
    public function generateTransactionPinResetCodeModel(): TransactionPinResetCodeModelData;

    /**
     * Transaction pin reset code associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function transactionPinResetCode(): MorphOne;

    /**
     * Generate a random code.
     *
     * @return string
     */
    public function generateTransactionPinResetCode(): string;

    /**
     * Send the transaction pin reset notification.
     *
     * @return void
     */
    public function sendTransactionPinResetNotification(): void;

    /**
     * Send the transaction pin updated notification.
     *
     * @return void
     */
    public function sendTransactionPinUpdatedNotification(): void;

    /**
     * Update the transaction PIN.
     *
     * @param string $pin
     * @return void
     */
    public function updateTransactionPin(string $pin): void;

    /**
     * Toggle the transaction PIN activation.
     *
     * @return void
     */
    public function toggleTransactionPinActivation(): void;
}

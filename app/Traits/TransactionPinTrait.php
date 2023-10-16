<?php

namespace App\Traits;

use App\DataTransferObjects\Models\TransactionPinResetCodeModelData;
use App\Models\TransactionPinResetCode;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait TransactionPinTrait
{
    /**
     * Generate code for resetting transaction pin.
     *
     * @return \App\DataTransferObjects\Models\TransactionPinResetCodeModelData
     */
    public function generateTransactionPinResetCodeModel(): TransactionPinResetCodeModelData
    {
        DB::beginTransaction();

        try {
            $this->transactionPinResetCode()->delete();

            $transactionPinResetCode = new TransactionPinResetCode();
            $transactionPinResetCode->user()->associate($this);

            $code = $this->generateTransactionPinResetCode();

            $transactionPinResetCode->code = Hash::make($code);
            $transactionPinResetCode->saveOrFail();

            DB::commit();

            return (new TransactionPinResetCodeModelData())->setUser($this)->setCode($code);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Transaction pin reset code associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function transactionPinResetCode(): MorphOne
    {
        return $this->morphOne(TransactionPinResetCode::class, 'user');
    }

    /**
     * Generate a random code.
     *
     * @return string
     */
    public function generateTransactionPinResetCode(): string
    {
        return (string) mt_rand(100000, 999999);
    }

    /**
     * Send the transaction pin reset notification.
     *
     * @return void
     */
    public function sendTransactionPinResetNotification(): void
    {
        $twoFaVerificationModelData = $this->generateTransactionPinResetCodeModel();

        $this->notifyNow(new \App\Notifications\Auth\TransactionPinResetNotification(
            $twoFaVerificationModelData->getCode(),
        ));
    }

    /**
     * Send the transaction pin updated notification.
     *
     * @return void
     */
    public function sendTransactionPinUpdatedNotification(): void
    {
        $this->notify(new \App\Notifications\Auth\TransactionPinUpdatedNotification());
    }

    /**
     * Update the transaction PIN.
     *
     * @param string $pin
     * @return void
     */
    public function updateTransactionPin(string $pin): void
    {
        $this->transaction_pin_set = true;
        $this->transaction_pin = Hash::make($pin);
        $this->saveOrFail();
    }

    /**
     * Toggle the transaction PIN activation.
     *
     * @return void
     */
    public function toggleTransactionPinActivation(): void
    {
        $this->transaction_pin_activated_at = $this->transaction_pin_activated_at ? null : now();
        $this->saveOrFail();
    }
}

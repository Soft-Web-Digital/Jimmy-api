<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Auth\HasTransactionPin;
use App\Exceptions\NotAllowedException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TransactionPinService
{
    /**
     * Update user transaction pin.
     *
     * @param \App\Contracts\Auth\HasTransactionPin $user
     * @param string $pin
     * @return void
     */
    public function update(HasTransactionPin $user, string $pin): void
    {
        activity()->disableLogging();

        $user->updateTransactionPin($pin);

        $user->sendTransactionPinUpdatedNotification();
    }

    /**
     * Request a transaction pin reset code.
     *
     * @param \App\Contracts\Auth\HasTransactionPin $user
     * @return void
     */
    public function requestResetCode(HasTransactionPin $user): void
    {
        $user->sendTransactionPinResetNotification();
    }

    /**
     * Reset transaction pin.
     *
     * @param \App\Contracts\Auth\HasTransactionPin $user
     * @param string $code
     * @param string $pin
     * @return void
     */
    public function reset(HasTransactionPin $user, string $code, string $pin): void
    {
        /** @var \App\Models\TransactionPinResetCode $transactionPinResetCode */
        $transactionPinResetCode = $user->transactionPinResetCode()->first();

        if (!$transactionPinResetCode || !Hash::check($code, $transactionPinResetCode->code)) {
            throw ValidationException::withMessages([
                'code' => [trans('auth.code.invalid')],
            ]);
        }

        if ($transactionPinResetCode->isExpired()) {
            throw ValidationException::withMessages([
                'code' => [trans('auth.code.expired')],
            ]);
        }

        $transactionPinResetCode->delete();

        $this->update($user, $pin);
    }

    /**
     * Toggle the activation of the transaction pin.
     *
     * @param \App\Contracts\Auth\HasTransactionPin $user
     * @return bool
     */
    public function toggleActivation(HasTransactionPin $user): bool
    {
        throw_if(
            !$user->transaction_pin_set,
            NotAllowedException::class,
            'You need to set a transaction PIN'
        );

        $user->toggleTransactionPinActivation();

        return (bool) $user->transaction_pin_activated_at;
    }
}

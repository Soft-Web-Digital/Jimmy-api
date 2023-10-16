<?php

namespace App\Rules;

use App\Contracts\HasWallet;
use Illuminate\Contracts\Validation\Rule;

class WalletBalanceRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param \App\Contracts\HasWallet $user
     * @return void
     */
    public function __construct(protected HasWallet $user)
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $this->user->wallet_balance - (float) $value >= 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.lte.numeric', [
            'value' => $this->user->wallet_balance,
        ]);
    }
}

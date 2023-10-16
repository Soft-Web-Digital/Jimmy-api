<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CompromisedTransactionPinRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
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
        return !in_array($value, [
            0000,
            1234,
        ]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.transaction_pin.uncompromised');
    }
}

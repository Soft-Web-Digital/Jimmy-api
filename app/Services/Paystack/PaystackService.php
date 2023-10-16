<?php

namespace App\Services\Paystack;

use Yabacon\Paystack;

class PaystackService
{
    protected Paystack $paystack;

    /**
     * Create a new Paystack service instance.
     */
    public function __construct()
    {
        $this->paystack = new Paystack(config('paystack.secret_key'));
    }

    /**
     * Get the Paystack library service.
     *
     * @return \Yabacon\Paystack
     */
    public function getFactory(): Paystack
    {
        return $this->paystack;
    }
}

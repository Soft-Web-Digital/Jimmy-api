<?php

namespace App\Providers;

use App\Contracts\CanVerifyBankAccount;
use App\Contracts\CanVerifyBvn;
use App\Services\Paystack\PaystackBankAccountService;
use App\Services\VerifiedAfrica\VerifiedAfricaBvnService;
use Illuminate\Support\ServiceProvider;

class VerifierServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CanVerifyBankAccount::class, function () {
            return new PaystackBankAccountService();
        });

        $this->app->bind(CanVerifyBvn::class, function () {
            return new VerifiedAfricaBvnService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

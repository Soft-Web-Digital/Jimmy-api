<?php

namespace App\Providers;

use App\Contracts\Fillers\BankFiller;
use App\Contracts\Fillers\CountryFiller;
use App\Contracts\Fillers\CurrencyFiller;
use App\Services\ApiLayer\ApiLayerCurrencyService;
use App\Services\Paystack\PaystackBankService;
use App\Services\RestCountries\RestCountriesCountryService;
use Illuminate\Support\ServiceProvider;

class FillerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CountryFiller::class, function () {
            return new RestCountriesCountryService();
        });

        $this->app->bind(BankFiller::class, function () {
            return new PaystackBankService();
        });

        $this->app->bind(CurrencyFiller::class, function () {
            return new ApiLayerCurrencyService();
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

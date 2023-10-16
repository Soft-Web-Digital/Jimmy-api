<?php

declare(strict_types=1);

namespace App\Services\RestCountries;

use Illuminate\Support\Facades\Http;

class RestCountriesBaseService
{
    /**
     * Get the HTTP connection.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function connection(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl((string) config('_restcountries.base_url'));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\ApiLayer;

use Illuminate\Support\Facades\Http;

class ApiLayerBaseService
{
    /**
     * Get the HTTP connection.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function connection(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(config('_apilayer.base_url'))
            ->withHeaders([
                'apikey' => config('_apilayer.api_key'),
            ]);
    }
}

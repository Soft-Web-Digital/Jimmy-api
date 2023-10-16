<?php

namespace App\Services\VerifiedAfrica;

use Illuminate\Support\Facades\Http;

class VerifiedAfricaBaseService
{
    /**
     * Get the HTTP connection.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function connection(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(config('_verifiedafrica.api_url'))
            ->withHeaders([
                'apikey' => config('_verifiedafrica.api_key'),
                'userid' => config('_verifiedafrica.user_id'),
            ]);
    }
}

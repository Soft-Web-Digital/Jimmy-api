<?php

declare(strict_types=1);

namespace App\Services\Waverlite;

use Illuminate\Support\Facades\Http;

class WaverliteBaseService
{
    /**
     * Get the HTTP connection.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function connection(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl((string) config('_waverlite.base_url'))
            ->withHeaders([
                'Private-Key' => config('_waverlite.private_key'),
            ]);
    }
}

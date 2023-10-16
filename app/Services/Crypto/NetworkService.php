<?php

declare(strict_types=1);

namespace App\Services\Crypto;

use App\Models\Network;

class NetworkService
{
    /**
     * Create a network.
     *
     * @param string $name
     * @param string $walletAddress
     * @param string|null $comment
     * @return \App\Models\Network
     */
    public function create(string $name, string $walletAddress, string|null $comment = null): Network
    {
        return Network::query()->create([
            'name' => $name,
            'wallet_address' => $walletAddress,
            'comment' => $comment,
        ])->refresh();
    }

    /**
     * Update the network.
     *
     * @param \App\Models\Network $network
     * @param string|null $name
     * @param string|null $walletAddress
     * @param string|null $comment
     * @return \App\Models\Network
     */
    public function update(
        Network $network,
        string|null $name = null,
        string|null $walletAddress = null,
        string|null $comment = null
    ): Network {
        $network->updateOrFail([
            'name' => $name ?? $network->name,
            'wallet_address' => $walletAddress ?? $network->wallet_address,
            'comment' => $comment ?? $network->comment,
        ]);

        return $network->refresh();
    }
}

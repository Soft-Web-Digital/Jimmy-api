<?php

use App\Models\Network;
use App\Services\Crypto\NetworkService;

uses()->group('service', 'network');





it('can create a network', function () {
    $network = (new NetworkService())->create(fake()->word(), fake()->iban(prefix: '0X'));

    expect($network)->toBeInstanceOf(Network::class);

    test()->assertDatabaseHas('networks', [
        'id' => $network->id,
    ]);
});





it('can update a network', function ($data) {
    $attribute = $data['attribute'];
    $value = $data['value'];

    $network = Network::factory()->create();

    $networkService = match ($attribute) {
        'name' => (new NetworkService())->update(network: $network, name: $value),
        'wallet_address' => (new NetworkService())->update(network: $network, walletAddress: $value),
    };

    expect($network->$attribute)->toBe($networkService->$attribute);
})->with([
    'name' => fn () => [
        'attribute' => 'name',
        'value' => fake()->word(),
    ],
    'wallet_address' => fn () => [
        'attribute' => 'wallet_address',
        'value' => fake()->iban(prefix: '0X'),
    ],
]);

<?php

use App\Enums\ApiErrorCode;
use App\Enums\KycAttribute;
use App\Models\AssetTransaction;
use App\Models\Giftcard;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\WalletTransaction;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\json;

uses()->group('api', 'user', 'auth', 'route-access');





it('rejects unauthenticated user from hitting the user APIs', function ($route) {
    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/user{$path}")
        ->assertUnauthorized()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::GENERAL_ERROR->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
})->with([
    'verify email' => fn () => [
        'method' => 'POST',
        'path' => '/email/verify',
    ],
    'resend email verification' => fn () => [
        'method' => 'POST',
        'path' => '/email/resend',
    ],
    'logout' => fn () => [
        'method' => 'POST',
        'path' => '/logout',
    ],
    'logout others' => fn () => [
        'method' => 'POST',
        'path' => '/logout-others',
    ],
]);





it('rejects two-fa required user from hitting the user APIs', function ($route) {
    sanctumLogin(User::factory()->create(), ['two_fa'], 'api_user');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/user{$path}")
        ->assertUnauthorized()
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::TWO_FACTOR_AUTHENTICATION_REQUIRED->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
})->with([
    'update profile password' => fn () => [
        'method' => 'POST',
        'path' => '/profile/password',
    ],
    'update profile two-fa' => fn () => [
        'method' => 'POST',
        'path' => '/profile/two-fa',
    ],
    'update profile' => fn () => [
        'method' => 'PATCH',
        'path' => '/profile',
    ],
    'get kyc' => fn () => [
        'method' => 'GET',
        'path' => '/kyc',
    ],
    'verify kyc' => fn () => [
        'method' => 'POST',
        'path' => '/kyc/verify/' . KycAttribute::random()->value,
    ],
    'update transaction pin' => fn () => [
        'method' => 'PATCH',
        'path' => '/transaction-pin',
    ],
    'forgot transaction pin' => fn () => [
        'method' => 'POST',
        'path' => '/transaction-pin/forgot',
    ],
    'reset transaction pin' => fn () => [
        'method' => 'POST',
        'path' => '/transaction-pin/reset',
    ],
    'users index' => fn () => [
        'method' => 'GET',
        'path' => '/users',
    ],
    'users transfer' => fn () => [
        'method' => 'POST',
        'path' => '/users/' . User::factory()->create()->id . '/transfer',
    ],
    'notifications index' => fn () => [
        'method' => 'GET',
        'path' => '/notifications',
    ],
    'notifications read' => fn () => [
        'method' => 'POST',
        'path' => '/notifications/read',
    ],
    'wallet transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions',
    ],
    'wallet transactions withdraw' => fn () => [
        'method' => 'POST',
        'path' => '/wallet-transactions/withdraw',
    ],
    'wallet transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id,
    ],
    'wallet transactions close' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/close',
    ],
    'bank accounts index' => fn () => [
        'method' => 'GET',
        'path' => '/bank-accounts',
    ],
    'bank accounts verify' => fn () => [
        'method' => 'POST',
        'path' => '/bank-accounts/verify',
    ],
    'bank accounts store' => fn () => [
        'method' => 'POST',
        'path' => '/bank-accounts',
    ],
    'bank accounts delete' => fn () => [
        'method' => 'DELETE',
        'path' => '/bank-accounts/' . UserBankAccount::factory()->create()->id,
    ],
    'giftcards index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards',
    ],
    'giftcards stats' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards/stats',
    ],
    'giftcards breakdown' => fn () => [
        'method' => 'POST',
        'path' => '/giftcards/breakdown',
    ],
    'giftcards sale' => fn () => [
        'method' => 'POST',
        'path' => '/giftcards/sale',
    ],
    'giftcards show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id,
    ],
    'giftcards delete' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id,
    ],
    'asset transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions',
    ],
    'asset transactions stats' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions/stats',
    ],
    'asset transactions breakdown' => fn () => [
        'method' => 'POST',
        'path' => '/asset-transactions/breakdown',
    ],
    'asset transactions store' => fn () => [
        'method' => 'POST',
        'path' => '/asset-transactions',
    ],
    'asset transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id,
    ],
    'asset transactions transfer' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/transfer',
    ],
    'transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/transactions',
    ],
]);





it('rejects blocked user from hitting the user APIs', function ($route) {
    sanctumLogin(User::factory()->blocked()->create(), ['*'], 'api_user');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/user{$path}")
        ->assertStatus(Response::HTTP_LOCKED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::BLOCKED_ACCESS->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
})->with([
    'update profile password' => fn () => [
        'method' => 'POST',
        'path' => '/profile/password',
    ],
    'update profile two-fa' => fn () => [
        'method' => 'POST',
        'path' => '/profile/two-fa',
    ],
    'update profile' => fn () => [
        'method' => 'PATCH',
        'path' => '/profile',
    ],
    'get kyc' => fn () => [
        'method' => 'GET',
        'path' => '/kyc',
    ],
    'verify kyc' => fn () => [
        'method' => 'POST',
        'path' => '/kyc/verify/' . KycAttribute::random()->value,
    ],
    'update transaction pin' => fn () => [
        'method' => 'PATCH',
        'path' => '/transaction-pin',
    ],
    'forgot transaction pin' => fn () => [
        'method' => 'POST',
        'path' => '/transaction-pin/forgot',
    ],
    'reset transaction pin' => fn () => [
        'method' => 'POST',
        'path' => '/transaction-pin/reset',
    ],
    'users index' => fn () => [
        'method' => 'GET',
        'path' => '/users',
    ],
    'users transfer' => fn () => [
        'method' => 'POST',
        'path' => '/users/' . User::factory()->create()->id . '/transfer',
    ],
    'notifications index' => fn () => [
        'method' => 'GET',
        'path' => '/notifications',
    ],
    'notifications read' => fn () => [
        'method' => 'POST',
        'path' => '/notifications/read',
    ],
    'wallet transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions',
    ],
    'wallet transactions withdraw' => fn () => [
        'method' => 'POST',
        'path' => '/wallet-transactions/withdraw',
    ],
    'wallet transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id,
    ],
    'wallet transactions close' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/close',
    ],
    'bank accounts index' => fn () => [
        'method' => 'GET',
        'path' => '/bank-accounts',
    ],
    'bank accounts verify' => fn () => [
        'method' => 'POST',
        'path' => '/bank-accounts/verify',
    ],
    'bank accounts store' => fn () => [
        'method' => 'POST',
        'path' => '/bank-accounts',
    ],
    'bank accounts delete' => fn () => [
        'method' => 'DELETE',
        'path' => '/bank-accounts/' . UserBankAccount::factory()->create()->id,
    ],
    'giftcards index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards',
    ],
    'giftcards stats' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards/stats',
    ],
    'giftcards breakdown' => fn () => [
        'method' => 'POST',
        'path' => '/giftcards/breakdown',
    ],
    'giftcards sale' => fn () => [
        'method' => 'POST',
        'path' => '/giftcards/sale',
    ],
    'giftcards show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id,
    ],
    'giftcards delete' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id,
    ],
    'asset transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions',
    ],
    'asset transactions stats' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions/stats',
    ],
    'asset transactions breakdown' => fn () => [
        'method' => 'POST',
        'path' => '/asset-transactions/breakdown',
    ],
    'asset transactions store' => fn () => [
        'method' => 'POST',
        'path' => '/asset-transactions',
    ],
    'asset transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id,
    ],
    'asset transactions transfer' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/transfer',
    ],
    'transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/transactions',
    ],
]);

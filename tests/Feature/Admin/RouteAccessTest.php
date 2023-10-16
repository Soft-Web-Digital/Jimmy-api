<?php

use App\Enums\ApiErrorCode;
use App\Enums\WalletTransactionType;
use App\Models\Admin;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\AssetTransaction;
use App\Models\Banner;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Giftcard;
use App\Models\GiftcardCategory;
use App\Models\GiftcardProduct;
use App\Models\Network;
use App\Models\Role;
use App\Models\SystemData;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\json;

uses()->group('api', 'admin', 'auth', 'route-access');





it('rejects unauthenticated admin from hitting the admin APIs', function ($route) {
    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin{$path}")
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





it('rejects two-fa required admin from hitting the admin APIs', function ($route) {
    sanctumLogin(Admin::factory()->create(), ['two_fa'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin{$path}")
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
    'countries index' => fn () => [
        'method' => 'GET',
        'path' => '/countries',
    ],
    'countries show' => fn () => [
        'method' => 'GET',
        'path' => '/countries/' . Country::factory()->create()->id,
    ],
    'countries toggle registration' => fn () => [
        'method' => 'PATCH',
        'path' => '/countries/' . Country::factory()->create()->id . '/registration',
    ],
    'countries toggle giftcard' => fn () => [
        'method' => 'PATCH',
        'path' => '/countries/' . Country::factory()->create()->id . '/giftcard',
    ],
    'alerts index' => fn () => [
        'method' => 'GET',
        'path' => '/alerts',
    ],
    'alerts store' => fn () => [
        'method' => 'POST',
        'path' => '/alerts',
    ],
    'alerts show' => fn () => [
        'method' => 'GET',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts update' => fn () => [
        'method' => 'PATCH',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/alerts/' . Alert::factory()->create()->id . '/restore',
    ],
    'alerts dispatch' => fn () => [
        'method' => 'POST',
        'path' => '/alerts/' . Alert::factory()->create()->id . '/dispatch',
    ],
    'admins index' => fn () => [
        'method' => 'GET',
        'path' => '/admins',
    ],
    'admins store' => fn () => [
        'method' => 'POST',
        'path' => '/admins',
    ],
    'admins show' => fn () => [
        'method' => 'GET',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins update' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->deleted()->create()->id . '/restore',
    ],
    'admins toggle block' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id . '/block',
    ],
    'admins toggle role' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id . '/role',
    ],
    'permissions index' => fn () => [
        'method' => 'GET',
        'path' => '/permissions',
    ],
    'my-permissions index' => fn () => [
        'method' => 'GET',
        'path' => '/my-permissions',
    ],
    'roles index' => fn () => [
        'method' => 'GET',
        'path' => '/roles',
    ],
    'roles store' => fn () => [
        'method' => 'POST',
        'path' => '/roles',
    ],
    'roles show' => fn () => [
        'method' => 'GET',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'roles update' => fn () => [
        'method' => 'PATCH',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'roles destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'notifications index' => fn () => [
        'method' => 'GET',
        'path' => '/notifications',
    ],
    'notifications read' => fn () => [
        'method' => 'POST',
        'path' => '/notifications/read',
    ],
    'users index' => fn () => [
        'method' => 'GET',
        'path' => '/users',
    ],
    'users show' => fn () => [
        'method' => 'GET',
        'path' => '/users/aa',
    ],
    'users block' => fn () => [
        'method' => 'PATCH',
        'path' => '/users/' . User::factory()->create()->id . '/block',
    ],
    'users finance' => fn () => [
        'method' => 'POST',
        'path' => '/users/' . User::factory()->create()->id . '/finance/' . WalletTransactionType::random()->value,
    ],
    'system data index' => fn () => [
        'method' => 'GET',
        'path' => '/system-data',
    ],
    'system data show' => fn () => [
        'method' => 'GET',
        'path' => '/system-data/' . SystemData::factory()->create()->id,
    ],
    'system data update' => fn () => [
        'method' => 'PATCH',
        'path' => '/system-data/' . SystemData::factory()->create()->id,
    ],
    'currencies update' => fn () => [
        'method' => 'PATCH',
        'path' => '/currencies/' . Currency::factory()->create()->id,
    ],
    'giftcard categories index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-categories',
    ],
    'giftcard categories store' => fn () => [
        'method' => 'POST',
        'path' => '/giftcard-categories',
    ],
    'giftcard categories show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories update' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/restore',
    ],
    'giftcard categories toggle sale activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/sale-activation',
    ],
    'giftcard categories toggle purchase activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/purchase-activation',
    ],
    'giftcard products index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-products',
    ],
    'giftcard products store' => fn () => [
        'method' => 'POST',
        'path' => '/giftcard-products',
    ],
    'giftcard products show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products update' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id . '/restore',
    ],
    'giftcard products toggle activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id . '/activation',
    ],
    'giftcards index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards',
    ],
    'giftcards show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id,
    ],
    'giftcards decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id . '/decline',
    ],
    'giftcards approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id . '/approve',
    ],
    'networks index' => fn () => [
        'method' => 'GET',
        'path' => '/networks',
    ],
    'networks store' => fn () => [
        'method' => 'POST',
        'path' => '/networks',
    ],
    'networks show' => fn () => [
        'method' => 'GET',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks update' => fn () => [
        'method' => 'PATCH',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/networks/' . Network::factory()->create()->id . '/restore',
    ],
    'assets index' => fn () => [
        'method' => 'GET',
        'path' => '/assets',
    ],
    'assets store' => fn () => [
        'method' => 'POST',
        'path' => '/assets',
    ],
    'assets show' => fn () => [
        'method' => 'GET',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets update' => fn () => [
        'method' => 'PATCH',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/assets/' . Asset::factory()->create()->id . '/restore',
    ],
    'asset transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions',
    ],
    'asset transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id,
    ],
    'asset transactions decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/decline',
    ],
    'asset transactions approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/approve',
    ],
    'wallet transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions',
    ],
    'wallet transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id,
    ],
    'wallet transactions decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/decline',
    ],
    'wallet transactions approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/approve',
    ],
    'banners index' => fn () => [
        'method' => 'GET',
        'path' => '/banners',
    ],
    'banners store' => fn () => [
        'method' => 'POST',
        'path' => '/banners',
    ],
    'banners show' => fn () => [
        'method' => 'GET',
        'path' => '/banners/' . Banner::factory()->create()->id,
    ],
    'banners destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/banners/' . Banner::factory()->create()->id,
    ],
]);





it('rejects blocked admin from hitting the admin APIs', function ($route) {
    sanctumLogin(Admin::factory()->blocked()->create(), ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin{$path}")
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
    'countries index' => fn () => [
        'method' => 'GET',
        'path' => '/countries',
    ],
    'countries show' => fn () => [
        'method' => 'GET',
        'path' => '/countries/' . Country::factory()->create()->id,
    ],
    'countries toggle registration' => fn () => [
        'method' => 'PATCH',
        'path' => '/countries/' . Country::factory()->create()->id . '/registration',
    ],
    'countries toggle giftcard' => fn () => [
        'method' => 'PATCH',
        'path' => '/countries/' . Country::factory()->create()->id . '/giftcard',
    ],
    'alerts index' => fn () => [
        'method' => 'GET',
        'path' => '/alerts',
    ],
    'alerts store' => fn () => [
        'method' => 'POST',
        'path' => '/alerts',
    ],
    'alerts show' => fn () => [
        'method' => 'GET',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts update' => fn () => [
        'method' => 'PATCH',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/alerts/' . Alert::factory()->create()->id . '/restore',
    ],
    'alerts dispatch' => fn () => [
        'method' => 'POST',
        'path' => '/alerts/' . Alert::factory()->create()->id . '/dispatch',
    ],
    'admins index' => fn () => [
        'method' => 'GET',
        'path' => '/admins',
    ],
    'admins store' => fn () => [
        'method' => 'POST',
        'path' => '/admins',
    ],
    'admins show' => fn () => [
        'method' => 'GET',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins update' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->deleted()->create()->id . '/restore',
    ],
    'admins toggle block' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id . '/block',
    ],
    'admins toggle role' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id . '/role',
    ],
    'permissions index' => fn () => [
        'method' => 'GET',
        'path' => '/permissions',
    ],
    'my-permissions index' => fn () => [
        'method' => 'GET',
        'path' => '/my-permissions',
    ],
    'roles index' => fn () => [
        'method' => 'GET',
        'path' => '/roles',
    ],
    'roles store' => fn () => [
        'method' => 'POST',
        'path' => '/roles',
    ],
    'roles show' => fn () => [
        'method' => 'GET',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'roles update' => fn () => [
        'method' => 'PATCH',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'roles destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'notifications index' => fn () => [
        'method' => 'GET',
        'path' => '/notifications',
    ],
    'notifications read' => fn () => [
        'method' => 'POST',
        'path' => '/notifications/read',
    ],
    'users index' => fn () => [
        'method' => 'GET',
        'path' => '/users',
    ],
    'users show' => fn () => [
        'method' => 'GET',
        'path' => '/users/aa',
    ],
    'users block' => fn () => [
        'method' => 'PATCH',
        'path' => '/users/' . User::factory()->create()->id . '/block',
    ],
    'users finance' => fn () => [
        'method' => 'POST',
        'path' => '/users/' . User::factory()->create()->id . '/finance/' . WalletTransactionType::random()->value,
    ],
    'system data index' => fn () => [
        'method' => 'GET',
        'path' => '/system-data',
    ],
    'system data show' => fn () => [
        'method' => 'GET',
        'path' => '/system-data/' . SystemData::factory()->create()->id,
    ],
    'system data update' => fn () => [
        'method' => 'PATCH',
        'path' => '/system-data/' . SystemData::factory()->create()->id,
    ],
    'currencies update' => fn () => [
        'method' => 'PATCH',
        'path' => '/currencies/' . Currency::factory()->create()->id,
    ],
    'giftcard categories index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-categories',
    ],
    'giftcard categories store' => fn () => [
        'method' => 'POST',
        'path' => '/giftcard-categories',
    ],
    'giftcard categories show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories update' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/restore',
    ],
    'giftcard categories toggle sale activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/sale-activation',
    ],
    'giftcard categories toggle purchase activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/purchase-activation',
    ],
    'giftcard products index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-products',
    ],
    'giftcard products store' => fn () => [
        'method' => 'POST',
        'path' => '/giftcard-products',
    ],
    'giftcard products show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products update' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id . '/restore',
    ],
    'giftcard products toggle activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id . '/activation',
    ],
    'giftcards index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards',
    ],
    'giftcards show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id,
    ],
    'giftcards decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id . '/decline',
    ],
    'giftcards approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id . '/approve',
    ],
    'networks index' => fn () => [
        'method' => 'GET',
        'path' => '/networks',
    ],
    'networks store' => fn () => [
        'method' => 'POST',
        'path' => '/networks',
    ],
    'networks show' => fn () => [
        'method' => 'GET',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks update' => fn () => [
        'method' => 'PATCH',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/networks/' . Network::factory()->create()->id . '/restore',
    ],
    'assets index' => fn () => [
        'method' => 'GET',
        'path' => '/assets',
    ],
    'assets store' => fn () => [
        'method' => 'POST',
        'path' => '/assets',
    ],
    'assets show' => fn () => [
        'method' => 'GET',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets update' => fn () => [
        'method' => 'PATCH',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/assets/' . Asset::factory()->create()->id . '/restore',
    ],
    'asset transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions',
    ],
    'asset transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id,
    ],
    'asset transactions decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/decline',
    ],
    'asset transactions approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/approve',
    ],
    'wallet transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions',
    ],
    'wallet transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id,
    ],
    'wallet transactions decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/decline',
    ],
    'wallet transactions approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/approve',
    ],
    'banners index' => fn () => [
        'method' => 'GET',
        'path' => '/banners',
    ],
    'banners store' => fn () => [
        'method' => 'POST',
        'path' => '/banners',
    ],
    'banners show' => fn () => [
        'method' => 'GET',
        'path' => '/banners/' . Banner::factory()->create()->id,
    ],
    'banners destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/banners/' . Banner::factory()->create()->id,
    ],
]);





it('rejects admin with unprotected password from hitting the admin API', function ($route) {
    $admin = Admin::factory()->create()->refresh();

    sanctumLogin($admin, ['*'], 'api_admin');

    ['method' => $method, 'path' => $path] = $route;

    json($method, "/api/admin{$path}")
        ->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJson(
            fn (AssertableJson $json) =>
                $json->where('success', false)
                    ->where('code', ApiErrorCode::INSECURE_PASSWORD->value)
                    ->where('locale', 'en')
                    ->whereType('message', 'string')
                    ->whereType('data', 'null')
        );
})->with([
    'update profile' => fn () => [
        'method' => 'PATCH',
        'path' => '/profile',
    ],
    'countries index' => fn () => [
        'method' => 'GET',
        'path' => '/countries',
    ],
    'countries show' => fn () => [
        'method' => 'GET',
        'path' => '/countries/' . Country::factory()->create()->id,
    ],
    'countries toggle registration' => fn () => [
        'method' => 'PATCH',
        'path' => '/countries/' . Country::factory()->create()->id . '/registration',
    ],
    'countries toggle giftcard' => fn () => [
        'method' => 'PATCH',
        'path' => '/countries/' . Country::factory()->create()->id . '/giftcard',
    ],
    'alerts index' => fn () => [
        'method' => 'GET',
        'path' => '/alerts',
    ],
    'alerts store' => fn () => [
        'method' => 'POST',
        'path' => '/alerts',
    ],
    'alerts show' => fn () => [
        'method' => 'GET',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts update' => fn () => [
        'method' => 'PATCH',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/alerts/' . Alert::factory()->create()->id,
    ],
    'alerts restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/alerts/' . Alert::factory()->create()->id . '/restore',
    ],
    'alerts dispatch' => fn () => [
        'method' => 'POST',
        'path' => '/alerts/' . Alert::factory()->create()->id . '/dispatch',
    ],
    'admins index' => fn () => [
        'method' => 'GET',
        'path' => '/admins',
    ],
    'admins store' => fn () => [
        'method' => 'POST',
        'path' => '/admins',
    ],
    'admins show' => fn () => [
        'method' => 'GET',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins update' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/admins/' . Admin::factory()->create()->id,
    ],
    'admins restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->deleted()->create()->id . '/restore',
    ],
    'admins toggle block' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id . '/block',
    ],
    'admins toggle role' => fn () => [
        'method' => 'PATCH',
        'path' => '/admins/' . Admin::factory()->create()->id . '/role',
    ],
    'permissions index' => fn () => [
        'method' => 'GET',
        'path' => '/permissions',
    ],
    'my-permissions index' => fn () => [
        'method' => 'GET',
        'path' => '/my-permissions',
    ],
    'roles index' => fn () => [
        'method' => 'GET',
        'path' => '/roles',
    ],
    'roles store' => fn () => [
        'method' => 'POST',
        'path' => '/roles',
    ],
    'roles show' => fn () => [
        'method' => 'GET',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'roles update' => fn () => [
        'method' => 'PATCH',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'roles destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/roles/' . Role::factory()->create()->id,
    ],
    'notifications index' => fn () => [
        'method' => 'GET',
        'path' => '/notifications',
    ],
    'notifications read' => fn () => [
        'method' => 'POST',
        'path' => '/notifications/read',
    ],
    'users index' => fn () => [
        'method' => 'GET',
        'path' => '/users',
    ],
    'users show' => fn () => [
        'method' => 'GET',
        'path' => '/users/aa',
    ],
    'users block' => fn () => [
        'method' => 'PATCH',
        'path' => '/users/' . User::factory()->create()->id . '/block',
    ],
    'users finance' => fn () => [
        'method' => 'POST',
        'path' => '/users/' . User::factory()->create()->id . '/finance/' . WalletTransactionType::random()->value,
    ],
    'system data index' => fn () => [
        'method' => 'GET',
        'path' => '/system-data',
    ],
    'system data show' => fn () => [
        'method' => 'GET',
        'path' => '/system-data/' . SystemData::factory()->create()->id,
    ],
    'system data update' => fn () => [
        'method' => 'PATCH',
        'path' => '/system-data/' . SystemData::factory()->create()->id,
    ],
    'currencies update' => fn () => [
        'method' => 'PATCH',
        'path' => '/currencies/' . Currency::factory()->create()->id,
    ],
    'giftcard categories index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-categories',
    ],
    'giftcard categories store' => fn () => [
        'method' => 'POST',
        'path' => '/giftcard-categories',
    ],
    'giftcard categories show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories update' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id,
    ],
    'giftcard categories restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/restore',
    ],
    'giftcard categories toggle sale activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/sale-activation',
    ],
    'giftcard categories toggle purchase activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-categories/' . GiftcardCategory::factory()->create()->id . '/purchase-activation',
    ],
    'giftcard products index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-products',
    ],
    'giftcard products store' => fn () => [
        'method' => 'POST',
        'path' => '/giftcard-products',
    ],
    'giftcard products show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products update' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id,
    ],
    'giftcard products restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id . '/restore',
    ],
    'giftcard products toggle activation' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcard-products/' . GiftcardProduct::factory()->create()->id . '/activation',
    ],
    'giftcards index' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards',
    ],
    'giftcards show' => fn () => [
        'method' => 'GET',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id,
    ],
    'giftcards decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id . '/decline',
    ],
    'giftcards approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/giftcards/' . Giftcard::factory()->create()->id . '/approve',
    ],
    'networks index' => fn () => [
        'method' => 'GET',
        'path' => '/networks',
    ],
    'networks store' => fn () => [
        'method' => 'POST',
        'path' => '/networks',
    ],
    'networks show' => fn () => [
        'method' => 'GET',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks update' => fn () => [
        'method' => 'PATCH',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/networks/' . Network::factory()->create()->id,
    ],
    'networks restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/networks/' . Network::factory()->create()->id . '/restore',
    ],
    'assets index' => fn () => [
        'method' => 'GET',
        'path' => '/assets',
    ],
    'assets store' => fn () => [
        'method' => 'POST',
        'path' => '/assets',
    ],
    'assets show' => fn () => [
        'method' => 'GET',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets update' => fn () => [
        'method' => 'PATCH',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/assets/' . Asset::factory()->create()->id,
    ],
    'assets restore' => fn () => [
        'method' => 'PATCH',
        'path' => '/assets/' . Asset::factory()->create()->id . '/restore',
    ],
    'asset transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions',
    ],
    'asset transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id,
    ],
    'asset transactions decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/decline',
    ],
    'asset transactions approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/asset-transactions/' . AssetTransaction::factory()->create()->id . '/approve',
    ],
    'wallet transactions index' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions',
    ],
    'wallet transactions show' => fn () => [
        'method' => 'GET',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id,
    ],
    'wallet transactions decline' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/decline',
    ],
    'wallet transactions approve' => fn () => [
        'method' => 'PATCH',
        'path' => '/wallet-transactions/' . WalletTransaction::factory()->create()->id . '/approve',
    ],
    'banners index' => fn () => [
        'method' => 'GET',
        'path' => '/banners',
    ],
    'banners store' => fn () => [
        'method' => 'POST',
        'path' => '/banners',
    ],
    'banners show' => fn () => [
        'method' => 'GET',
        'path' => '/banners/' . Banner::factory()->create()->id,
    ],
    'banners destroy' => fn () => [
        'method' => 'DELETE',
        'path' => '/banners/' . Banner::factory()->create()->id,
    ],
]);

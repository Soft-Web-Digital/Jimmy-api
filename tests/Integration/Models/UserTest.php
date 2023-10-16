<?php

use App\Contracts\Auth\HasTransactionPin;
use App\Contracts\Auth\MustSatisfyTwoFa;
use App\Contracts\Auth\MustVerifyEmail;
use App\Contracts\HasWallet;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticable;

uses()->group('model', 'user');





it('can be authenticated, email verified and enabled for two-fa', function () {
    expect(User::factory()->create())
        ->toBeInstanceOf(Authenticable::class)
        ->toBeInstanceOf(MustSatisfyTwoFa::class)
        ->toBeInstanceOf(MustVerifyEmail::class);
});





it('uses the softdelete trait', function () {
    expect(in_array(SoftDeletes::class, class_uses(User::class)))->toBeTrue();
});





it('can be deleted', function () {
    $user = User::factory()->create();

    $user->delete();

    expect((bool) $user->deleted_at)->toBeTrue();
});





it('can be restored', function () {
    $user = User::factory()->deleted()->create();

    $user->restore();

    expect((bool) $user->deleted_at)->toBeFalse();
});





it('has a morph class of user', function () {
    expect((new User())->getMorphClass())
        ->toBe(strtolower((new ReflectionClass((new User())))->getShortName()));
});





it('implements the MustSatisfyTwoFa contract', function () {
    expect(new User())->toBeInstanceOf(MustSatisfyTwoFa::class);
});





it('implements the MustVerifyEmail contract', function () {
    expect(new User())->toBeInstanceOf(MustVerifyEmail::class);
});





it('implements the HasTransactionPin contract', function () {
    expect(new User())->toBeInstanceOf(HasTransactionPin::class);
});





it('implements the HasWallet contract', function () {
    expect(new User())->toBeInstanceOf(HasWallet::class);
});

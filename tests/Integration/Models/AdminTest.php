<?php

use App\Contracts\Auth\MustSatisfyTwoFa;
use App\Contracts\Auth\MustVerifyEmail;
use App\Contracts\HasRoleContract;
use App\Models\Admin;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

uses()->group('model', 'admin');





it('can be authenticated, email verified, has roles and enabled for two-fa', function () {
    expect(Admin::factory()->create())
        ->toBeInstanceOf(User::class)
        ->toBeInstanceOf(MustSatisfyTwoFa::class)
        ->toBeInstanceOf(HasRoleContract::class)
        ->toBeInstanceOf(MustVerifyEmail::class);
});





it('uses the softdelete trait', function () {
    expect(in_array(SoftDeletes::class, class_uses(Admin::class)))->toBeTrue();
});





it('can be deleted', function () {
    $admin = Admin::factory()->create();

    $admin->delete();

    expect((bool) $admin->deleted_at)->toBeTrue();
});





it('can be restored', function () {
    $admin = Admin::factory()->deleted()->create();

    $admin->restore();

    expect((bool) $admin->deleted_at)->toBeFalse();
});





it('has a morph class of admin', function () {
    expect((new Admin())->getMorphClass())
        ->toBe(strtolower((new ReflectionClass((new Admin())))->getShortName()));
});





it('implements the MustSatisfyTwoFa contract', function () {
    expect(new Admin())->toBeInstanceOf(MustSatisfyTwoFa::class);
});





it('implements the MustVerifyEmail contract', function () {
    expect(new Admin())->toBeInstanceOf(MustVerifyEmail::class);
});

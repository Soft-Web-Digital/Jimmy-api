<?php

use App\Events\PasswordUpdated;
use App\Services\Profile\ProfilePasswordService;
use Illuminate\Foundation\Auth\User as Authenticable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses()->group('service', 'profile');





it('updates user password', function ($user) {
    expect($user)->toBeInstanceOf(Authenticable::class);

    $newPassword = Str::random(8);

    (new ProfilePasswordService())->update($user, $newPassword);

    expect(Hash::check($newPassword, $user->password))->toBeTrue();

    if (isset($user->password_unprotected)) {
        expect($user)->password_unprotected->toBeFalse();
    }
})->with('authenticable_models');





it('fires the PasswordUpdated event on password change', function ($user) {
    Event::fake();

    (new ProfilePasswordService())->update($user, Str::random(8));

    Event::assertDispatched(PasswordUpdated::class);
})->with('authenticable_models');

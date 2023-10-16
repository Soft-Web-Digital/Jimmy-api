<?php

use App\Contracts\Auth\MustSatisfyTwoFa;
use App\Events\TwoFaStatusUpdated;
use App\Models\Admin;
use App\Models\User;
use App\Services\Profile\ProfileTwoFaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;

uses()->group('service', 'profile');





it('can get a two-fa enabled user', function ($user) {
    expect($user)->toBeInstanceOf(MustSatisfyTwoFa::class);
})->with('authenticable_models');





it('can enable two-FA status', function ($user) {
    expect((new ProfileTwoFaService())->toggle($user))->toBeBool();

    expect($user)->two_fa_activated_at->toBeInstanceOf(Carbon::class);
})->with('authenticable_models');





it('can disable two-FA status', function ($user) {
    expect((new ProfileTwoFaService())->toggle($user))->toBeBool();

    expect($user)->two_fa_activated_at->toBeNull();
})->with([
    'user' => fn () => User::factory()->twoFaEnabled()->create(),
    'admin' => fn () => Admin::factory()->twoFaEnabled()->create(),
]);





it('dispatches the TwoFaStatusUpdated event for user', function ($user) {
    Event::fake();

    (new ProfileTwoFaService())->toggle($user);

    Event::assertDispatched(TwoFaStatusUpdated::class);
})->with('authenticable_models');

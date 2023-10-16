<?php

use App\Enums\KycAttribute;
use App\Exceptions\NotAllowedException;
use App\Models\User;
use App\Models\UserKyc;
use App\Services\Profile\UserKycService;
use Illuminate\Support\Facades\Queue;

uses()->group('service', 'kyc');





it('can create a new kyc profile for a user', function ($kycAttribute) {
    $user = User::factory()->create()->refresh();

    expect($user->kyc()->first())->toBeNull();

    Queue::fake();

    $kycAttribute = KycAttribute::from($kycAttribute);

    (new UserKycService())->verify($user, $kycAttribute, fake()->md5());

    expect($user->kyc()->first())->not->toBeNull();

    Queue::assertPushed(get_class($kycAttribute->verificationJobClass($user, fake()->md5())));
})->with(array_map(fn ($attribute) => $attribute->value, KycAttribute::serviced()));





it('can update the kyc record for a user', function ($kycAttribute) {
    $user = User::factory()->create()->refresh();

    $kyc = UserKyc::factory()->create(['user_id' => $user->id, 'user_type' => $user->getMorphClass()])->refresh();

    expect($kyc->is($user->kyc()->first()))->toBeTrue();

    $type = KycAttribute::from($kycAttribute);
    $value = fake()->md5();

    (new UserKycService())->verify($user, $type, $value);

    expect($user->kyc()->first())->$kycAttribute->toBe($value);
})->with(array_map(fn ($attribute) => $attribute->value, KycAttribute::serviced()));





it('can unverifies the kyc profile when changed', function ($kycAttribute) {
    $user = User::factory()->create()->refresh();

    $method = "{$kycAttribute}Verified";
    UserKyc::factory()->$method()->create([
        'user_id' => $user->id,
        'user_type' => $user->getMorphClass(),
    ])->refresh();

    $type = KycAttribute::from($kycAttribute);
    $value = fake()->md5();

    (new UserKycService())->verify($user, $type, $value);

    $verified = "{$kycAttribute}_verified_at";
    expect($user->kyc()->first())->$verified->toBeNull();
})->with(array_map(fn ($attribute) => $attribute->value, KycAttribute::serviced()));





it('can reject a new verification when kyc is already verified', function ($kycAttribute) {
    $user = User::factory()->create()->refresh();

    $method = "{$kycAttribute}Verified";
    $kyc = UserKyc::factory()->$method()->create([
        'user_id' => $user->id,
        'user_type' => $user->getMorphClass(),
    ])->refresh();

    $record = "{$kyc->$kycAttribute}";
    $type = KycAttribute::from($kycAttribute);

    expect(fn () => (new UserKycService())->verify($user, $type, $record))
        ->toThrow(NotAllowedException::class, $type->name . ' has already been verified.');
})->with(array_map(fn ($attribute) => $attribute->value, KycAttribute::serviced()));

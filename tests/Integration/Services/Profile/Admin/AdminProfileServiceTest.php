<?php

use App\DataTransferObjects\Models\AdminModelData;
use App\Models\Admin;
use App\Models\Country;
use App\Services\Profile\Admin\AdminProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;

uses()->group('service', 'profile', 'admin');





it('fails to find the country from the countryId specified in AdminModelData', function ($uuid) {
    (new AdminProfileService())->update(Admin::factory()->create(), (new AdminModelData())->setCountryId($uuid));
})->with([
    'uuid1' => fn () => fake()->uuid(),
    'uuid2' => fn () => fake()->uuid(),
    'uuid3' => fn () => fake()->uuid(),
])->throws(ModelNotFoundException::class);





it('can upload avatar to storage', function () {
    $admin = Admin::factory()->create();

    Storage::fake();

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setAvatar(UploadedFile::fake()->image('avatar1.jpg'))
    );

    Storage::assertExists("avatars/{$admin->id}.jpg");
});





it('throws a NumberFormatException for invalid phone number against the country', function ($phoneNumber, $country) {
    (new AdminProfileService())->update(
        Admin::factory()->create(),
        (new AdminModelData())->setCountryId($country->id)->setPhoneNumber($phoneNumber)
    );
})->with([
    'au number' => '1800 801 920',
    'za number' => '27810005933',
])->with([
    'nga' => fn () => Country::factory()->create(['alpha2_code' => 'ng']),
])->throws(NumberParseException::class, 'Number does not match the provided country.');





it('can update the admin country', function () {
    $admin = Admin::factory()->create();

    $oldCountryId = $admin->country_id;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setCountryId(Country::factory()->create()->id)
    );

    $newCountryId = Admin::first()->country_id;

    expect($oldCountryId)->not->toBe($newCountryId);
});





it('can update the admin avatar', function () {
    $admin = Admin::factory()->create();

    $oldAvatar = $admin->avatar;

    Storage::fake();

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setAvatar(UploadedFile::fake()->image('avatar1.jpg'))
    );

    $newAvatar = Admin::first()->avatar;

    expect($oldAvatar)->not->toBe($newAvatar);
});





it('can update the admin phone number', function ($phoneNumber) {
    $admin = Admin::factory()->create(['country_id' => Country::factory()->create(['alpha2_code' => 'NG'])]);

    $oldPhoneNumber = $admin->phone_number;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setPhoneNumber($phoneNumber)
    );

    $newPhoneNumber = Admin::first()->phone_number;

    expect($oldPhoneNumber)->not->toBe($newPhoneNumber);
})->with(
    array_map(
        fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
        [
            '0703#######',
            '0704#######',
            '+234703#######',
            '+234704#######',
            '0703 ### ####',
            '0704 ### ####',
            '+234 703 ### ####',
            '+234 704 ### ####',
        ]
    )
);





it('can update the admin firstname', function () {
    $admin = Admin::factory()->create();

    $oldFirstname = $admin->firstname;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newFirstname = Admin::first()->firstname;

    expect($oldFirstname)->not->toBe($newFirstname);
});





it('can update the admin lastname', function () {
    $admin = Admin::factory()->create();

    $oldLastname = $admin->lastname;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setLastname(fake()->lastName())
    );

    $newLastname = Admin::first()->lastname;

    expect($oldLastname)->not->toBe($newLastname);
});





it('can update the admin email', function () {
    $admin = Admin::factory()->create();

    $oldEmail = $admin->email;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setEmail(fake()->email())
    );

    $newEmail = Admin::first()->email;

    expect($oldEmail)->not->toBe($newEmail);
});





it('unverifies the email if admin email changes', function () {
    $admin = Admin::factory()->verified()->create();

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setEmail(fake()->email())
    );

    $admin = Admin::latest()->first();

    expect($admin->email_verified_at)->toBeNull();
});





it('retains the admin country if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldCountryId = $admin->country_id;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newCountryId = Admin::first()->country_id;

    expect($oldCountryId)->toBe($newCountryId);
});





it('retains the admin avatar if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldAvatar = $admin->avatar;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newAvatar = Admin::first()->avatar;

    expect($oldAvatar)->toBe($newAvatar);
});





it('retains the admin phone number if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldPhoneNumber = $admin->phone_number;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newPhoneNumber = Admin::first()->phone_number;

    expect($oldPhoneNumber)->toBe($newPhoneNumber);
});





it('retains the admin firstname if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldFirstname = $admin->firstname;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setLastname(fake()->lastName())
    );

    $newFirstname = Admin::first()->firstname;

    expect($oldFirstname)->toBe($newFirstname);
});





it('retains the admin lastname if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldLastname = $admin->lastname;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newLastname = Admin::first()->lastname;

    expect($oldLastname)->toBe($newLastname);
});





it('retains the admin email if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldEmail = $admin->email;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newEmail = Admin::first()->email;

    expect($oldEmail)->toBe($newEmail);
});





it('does not change the verification status of the admin email if the email does not change', function () {
    $admin = Admin::factory()->create();

    $oldStatus = (string) $admin->email_verified_at;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newStatus = (string) Admin::first()->email_verified_at;

    expect($oldStatus)->toBe($newStatus);
});





it('does not change the verification status of verified admin email if the email does not change', function () {
    $admin = Admin::factory()->verified()->create();

    $oldStatus = (string) $admin->email_verified_at;

    (new AdminProfileService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newStatus = (string) Admin::first()->email_verified_at;

    expect($oldStatus)->toBe($newStatus);
});

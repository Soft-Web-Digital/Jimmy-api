<?php

use App\DataTransferObjects\Models\UserModelData;
use App\Models\Country;
use App\Models\User;
use App\Services\Profile\User\UserProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;

uses()->group('service', 'profile', 'user');





it('fails to find the country from the countryId specified in UserModelData', function ($uuid) {
    (new UserProfileService())->update(User::factory()->create(), (new UserModelData())->setCountryId($uuid));
})->with([
    'uuid1' => fn () => fake()->uuid(),
    'uuid2' => fn () => fake()->uuid(),
    'uuid3' => fn () => fake()->uuid(),
])->throws(ModelNotFoundException::class);





it('can upload avatar to storage', function () {
    $user = User::factory()->create();

    Storage::fake();

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setAvatar(UploadedFile::fake()->image("{$user->id}.jpg"))
    );

    Storage::assertExists("avatars/{$user->id}.jpg");
});





it('throws a NumberFormatException for invalid phone number against the country', function ($phoneNumber, $country) {
    (new UserProfileService())->update(
        User::factory()->create(),
        (new UserModelData())->setCountryId($country->id)->setPhoneNumber($phoneNumber)
    );
})->with([
    'au number' => '1800 801 920',
    'za number' => '27810005933',
])->with([
    'nga' => fn () => Country::factory()->create(['alpha2_code' => 'ng']),
])->throws(NumberParseException::class, 'Number does not match the provided country.');





it('can update the user country', function () {
    $user = User::factory()->create();

    $oldCountryId = $user->country_id;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setCountryId(Country::factory()->create()->id)
    );

    $newCountryId = User::first()->country_id;

    expect($oldCountryId)->not->toBe($newCountryId);
});





it('can update the user avatar', function () {
    $user = User::factory()->create();

    $oldAvatar = $user->avatar;

    Storage::fake();

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setAvatar(UploadedFile::fake()->image('avatar1.jpg'))
    );

    $newAvatar = User::first()->avatar;

    expect($oldAvatar)->not->toBe($newAvatar);
});





it('can update the user phone number', function ($phoneNumber) {
    $user = User::factory()->create(['country_id' => Country::factory()->create(['alpha2_code' => 'NG'])]);

    $oldPhoneNumber = $user->phone_number;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setPhoneNumber($phoneNumber)
    );

    $newPhoneNumber = User::first()->phone_number;

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





it('can update the user firstname', function () {
    $user = User::factory()->create();

    $oldFirstname = $user->firstname;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newFirstname = User::first()->firstname;

    expect($oldFirstname)->not->toBe($newFirstname);
});





it('can update the user lastname', function () {
    $user = User::factory()->create();

    $oldLastname = $user->lastname;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setLastname(fake()->lastName())
    );

    $newLastname = User::first()->lastname;

    expect($oldLastname)->not->toBe($newLastname);
});





it('can update the user email', function () {
    $user = User::factory()->create();

    $oldEmail = $user->email;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setEmail(fake()->email())
    );

    $newEmail = User::first()->email;

    expect($oldEmail)->not->toBe($newEmail);
});




it('can update the username', function () {
    $user = User::factory()->create();

    $oldUsername = $user->username;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setUsername(fake()->userName())
    );

    $newUsername = User::first()->username;

    expect($oldUsername)->not->toBe($newUsername);
});





it('unverifies the email if user email changes', function () {
    $user = User::factory()->verified()->create();

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setEmail(fake()->email())
    );

    $user = User::latest()->first();

    expect($user->email_verified_at)->toBeNull();
});





it('retains the user country if not specified in UserModelData', function () {
    $user = User::factory()->create();

    $oldCountryId = $user->country_id;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newCountryId = User::first()->country_id;

    expect($oldCountryId)->toBe($newCountryId);
});





it('retains the user avatar if not specified in UserModelData', function () {
    $user = User::factory()->create();

    $oldAvatar = $user->avatar;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newAvatar = User::first()->avatar;

    expect($oldAvatar)->toBe($newAvatar);
});





it('retains the user phone number if not specified in UserModelData', function () {
    $user = User::factory()->create();

    $oldPhoneNumber = $user->phone_number;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newPhoneNumber = User::first()->phone_number;

    expect($oldPhoneNumber)->toBe($newPhoneNumber);
});





it('retains the user firstname if not specified in UserModelData', function () {
    $user = User::factory()->create();

    $oldFirstname = $user->firstname;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setLastname(fake()->lastName())
    );

    $newFirstname = User::first()->firstname;

    expect($oldFirstname)->toBe($newFirstname);
});





it('retains the user lastname if not specified in UserModelData', function () {
    $user = User::factory()->create();

    $oldLastname = $user->lastname;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newLastname = User::first()->lastname;

    expect($oldLastname)->toBe($newLastname);
});





it('retains the user email if not specified in UserModelData', function () {
    $user = User::factory()->create();

    $oldEmail = $user->email;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newEmail = User::first()->email;

    expect($oldEmail)->toBe($newEmail);
});





it('retains the username if not specified in UserModelData', function () {
    $user = User::factory()->create();

    $oldUsername = $user->username;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newUsername = User::first()->username;

    expect($oldUsername)->toBe($newUsername);
});





it('does not change the verification status of the user email if the email does not change', function () {
    $user = User::factory()->create();

    $oldStatus = (string) $user->email_verified_at;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newStatus = (string) User::first()->email_verified_at;

    expect($oldStatus)->toBe($newStatus);
});





it('does not change the verification status of verified user email if the email does not change', function () {
    $user = User::factory()->verified()->create();

    $oldStatus = (string) $user->email_verified_at;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setFirstname(fake()->firstName())
    );

    $newStatus = (string) User::first()->email_verified_at;

    expect($oldStatus)->toBe($newStatus);
});





it('can update the date of birth', function () {
    $user = User::factory()->create();

    $oldDob = $user->date_of_birth;

    (new UserProfileService())->update(
        $user,
        (new UserModelData())->setDateOfBirth(fake()->date())
    );

    $newDob = User::first()->date_of_birth;

    expect($oldDob)->not->toBe($newDob);
});





it('can delete account', function () {
    $user = User::factory()->create();

    (new UserProfileService())->delete(
        $user,
        fake()->sentence()
    );

    $user = User::withTrashed()->find($user->id);

    expect($user)
        ->deleted_at->toBeInstanceOf(\Carbon\Carbon::class)
        ->deleted_reason->not->toBeEmpty();
});

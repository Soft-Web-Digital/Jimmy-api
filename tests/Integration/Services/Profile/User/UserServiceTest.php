<?php

use App\DataTransferObjects\Auth\AuthenticationCredentials;
use App\DataTransferObjects\Models\UserModelData;
use App\Models\Country;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Profile\User\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;

uses()->group('service', 'profile', 'user');





it('throws a ModelNotFoundException for unfound country in UserModelData', function () {
    $userModelData = (new UserModelData())->setCountryId(fake()->uuid());

    (new UserService())->create($userModelData);
})->throws(ModelNotFoundException::class);





it('throws a NumberParseException for an invalid phone number for NGA', function ($phoneNumber) {
    $userModelData = (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber($phoneNumber);

    (new UserService())->create($userModelData);
})->with(
    array_map(
        fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
        [
            '9## ## ####',
            '9## ######',
            '9########',
        ]
    )
)->throws(NumberParseException::class, 'Number does not match the provided country.');





it('throws a QueryException on unset firstname, lastname, email, password or username', function ($userModelData) {
    (new UserService())->create($userModelData);
})->with([
    'without firstname' => fn () => (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        )),
    'without lastname' => fn () => (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setEmail(fake()->unique()->email())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        )),
    'without email' => fn () => (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        )),
    'without password' => fn () => (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        )),
])->throws(QueryException::class);





it('can create a user', function () {
    $userModelData = (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        ))
        ->setDateOfBirth(fake()->date());

    expect((new UserService())->create($userModelData))
        ->toBeInstanceOf(User::class)
        ->firstname->toBe($userModelData->getFirstname())
        ->lastname->toBe($userModelData->getLastname())
        ->email->toBe($userModelData->getEmail())
        ->username->toBe($userModelData->getUsername())
        ->date_of_birth->toBeInstanceOf(\Carbon\Carbon::class);
});





it('can create a user on authentication mode', function () {
    $userModelData = (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        ));

    expect((new UserService())->create($userModelData, true))->toBeInstanceOf(AuthenticationCredentials::class);
});





it('fires a Registered event on user creation', function () {
    $userModelData = (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        ));

    Event::fake([
        Registered::class,
    ]);

    (new UserService())->create($userModelData, true);

    Event::assertDispatched(Registered::class);
});





it('authenticates the correct user on authentication mode', function () {
    $userModelData = (new UserModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
        ->setPassword(fake()->word())
        ->setUsername(fake()->unique()->userName())
        ->setPhoneNumber(fake()->randomElement(
            array_map(
                fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
                [
                    '0703#######',
                    '0704#######',
                    '0703 ### ####',
                    '0704 ### ####',
                ]
            )
        ));

    $authenticationCredentials = (new UserService())->create($userModelData, true);

    expect($authenticationCredentials->getUser())
        ->firstname->toBe($userModelData->getFirstname())
        ->lastname->toBe($userModelData->getLastname())
        ->email->toBe($userModelData->getEmail())
        ->username->toBe($userModelData->getUsername());

    expect($authenticationCredentials->getApiMessage())->toBe('User created successfully');

    test()->assertDatabaseHas((new PersonalAccessToken())->getTable(), [
        'tokenable_id' => $authenticationCredentials->getUser()->id,
        'tokenable_type' => $authenticationCredentials->getUser()->getMorphClass(),
        'abilities' => '["*"]',
    ]);
});

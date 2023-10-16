<?php

use App\DataTransferObjects\Models\AdminModelData;
use App\Events\Admin\Registered;
use App\Events\RoleAssigned;
use App\Listeners\Admin\SendWelcomeMail;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Role;
use App\Services\Profile\Admin\AdminService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;

uses()->group('service', 'profile', 'admin');





it('throws a ModelNotFoundException for unfound country in AdminModelData', function () {
    $userModelData = (new AdminModelData())->setCountryId(fake()->uuid());

    (new AdminService())->create($userModelData);
})->throws(ModelNotFoundException::class);





it('throws a QueryException if firstname, lastname, or email are not set', function ($adminModelData) {
    (new AdminService())->create($adminModelData);
})->with([
    'without firstname' => fn () => (new AdminModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email())
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
    'without lastname' => fn () => (new AdminModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setEmail(fake()->unique()->email())
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
    'without email' => fn () => (new AdminModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'NG'])->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
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





it('can create an admin', function () {
    $adminModelData = (new AdminModelData())
        ->setCountryId(Country::factory()->create()->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email());

    $admin = (new AdminService())->create($adminModelData);

    expect($admin)->toBeInstanceOf(Admin::class)
        ->id->toBe(Admin::latest()->value('id'));
});





it('can create an admin without country_id', function () {
    $adminModelData = (new AdminModelData())
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email());

    $admin = (new AdminService())->create($adminModelData);

    expect($admin)->toBeInstanceOf(Admin::class)
        ->id->toBe(Admin::latest()->value('id'));
});





it('fires a Registered event on successful admin creation', function () {
    $adminModelData = (new AdminModelData())
        ->setCountryId(Country::factory()->create()->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email());

    Event::fake([
        Registered::class,
    ]);

    (new AdminService())->create($adminModelData);

    Event::assertDispatched(Registered::class);

    Event::assertListening(Registered::class, SendWelcomeMail::class);
});





it('throws a QueryException if email is a duplicate', function () {
    $adminModelData = (new AdminModelData())
        ->setCountryId(Country::factory()->create()->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(Admin::factory()->create(['email' => fake()->email()])->email);

    (new AdminService())->create($adminModelData);
})->throws(QueryException::class);





it('can update an admin', function () {
    $admin = Admin::factory()->create();

    $adminModelData = (new AdminModelData())
        ->setCountryId(Country::factory()->create()->id)
        ->setFirstname(fake()->firstName())
        ->setLastname(fake()->lastName())
        ->setEmail(fake()->unique()->email());

    expect((new AdminService())->update($admin, $adminModelData))->toBeInstanceOf(Admin::class);
});





it('cannot update an admin with an existing phone number in NGA against an invalid country', function ($phoneNumber) {
    $admin = Admin::factory()->create([
        'phone_number' => $phoneNumber,
        'country_id' => Country::factory()->create(['alpha2_code' => 'NG'])
    ]);

    $adminModelData = (new AdminModelData())
        ->setCountryId(Country::factory()->create(['alpha2_code' => 'US'])->id);

    (new AdminService())->update($admin, $adminModelData);
})->with(
    array_map(
        fn ($phoneNumber) => str_replace('#', rand(0, 9), $phoneNumber),
        [
            '0703#######',
            '0704#######',
            '0703 ### ####',
            '0704 ### ####',
        ]
    )
)->throws(NumberParseException::class, 'Number does not match the provided country.');





it('unverifies the email if admin email changes', function () {
    $admin = Admin::factory()->verified()->create();

    (new AdminService())->update(
        $admin,
        (new AdminModelData())->setEmail(fake()->email())
    );

    $admin = Admin::latest()->first();

    expect($admin->email_verified_at)->toBeNull();
});





it('retains the admin country if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldCountryId = $admin->country_id;

    (new AdminService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newCountryId = Admin::first()->country_id;

    expect($oldCountryId)->toBe($newCountryId);
});





it('retains the admin firstname if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldFirstname = $admin->firstname;

    (new AdminService())->update(
        $admin,
        (new AdminModelData())->setLastname(fake()->lastName())
    );

    $newFirstname = Admin::first()->firstname;

    expect($oldFirstname)->toBe($newFirstname);
});





it('retains the admin lastname if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldLastname = $admin->lastname;

    (new AdminService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newLastname = Admin::first()->lastname;

    expect($oldLastname)->toBe($newLastname);
});





it('retains the admin email if not specified in AdminModelData', function () {
    $admin = Admin::factory()->create();

    $oldEmail = $admin->email;

    (new AdminService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newEmail = Admin::first()->email;

    expect($oldEmail)->toBe($newEmail);
});





it('does not change the verification status of the admin email if the email does not change', function () {
    $admin = Admin::factory()->create();

    $oldStatus = (string) $admin->email_verified_at;

    (new AdminService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newStatus = (string) Admin::first()->email_verified_at;

    expect($oldStatus)->toBe($newStatus);
});





it('does not change the verification status of verified admin email if the email does not change', function () {
    $admin = Admin::factory()->verified()->create();

    $oldStatus = (string) $admin->email_verified_at;

    (new AdminService())->update(
        $admin,
        (new AdminModelData())->setFirstname(fake()->firstName())
    );

    $newStatus = (string) Admin::first()->email_verified_at;

    expect($oldStatus)->toBe($newStatus);
});





it('toggle an admin role', function () {
    $admin = Admin::factory()->create();

    $role = Role::factory()->guard('api_admin')->create();

    expect((new AdminService())->toggleRole($admin, $role->id))->toBeInstanceOf(Admin::class);

    expect($admin->hasRole($role))->toBeTrue();
});





it('unassign old role and assign new role to admin', function () {
    $admin = Admin::factory()->create();

    $oldRole = Role::factory()->guard('api_admin')->create();
    $admin->assignRole($oldRole);

    $newRole = Role::factory()->guard('api_admin')->create();

    (new AdminService())->toggleRole($admin, $newRole->id);

    expect($admin->hasRole($newRole))->toBeTrue();
    expect($admin->hasRole($oldRole))->toBeFalse();
});





it('fires a RoleAssigned event when role is assigned', function () {
    $admin = Admin::factory()->create();

    $role = Role::factory()->guard('api_admin')->create();

    Event::fake();

    (new AdminService())->toggleRole($admin, $role->id);

    Event::assertDispatched(RoleAssigned::class);
});





it('does not fire a RoleAssigned event when role is assigned', function () {
    $admin = Admin::factory()->create();

    $oldRole = Role::factory()->guard('api_admin')->create();
    $admin->assignRole($oldRole);

    Event::fake();

    (new AdminService())->toggleRole($admin, $oldRole->id);

    Event::assertNotDispatched(RoleAssigned::class);
});

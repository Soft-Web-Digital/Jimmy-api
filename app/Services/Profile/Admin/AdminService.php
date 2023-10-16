<?php

declare(strict_types=1);

namespace App\Services\Profile\Admin;

use App\DataTransferObjects\Models\AdminModelData;
use App\Events\Admin\Registered;
use App\Events\RoleAssigned;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminService
{
    /**
     * Create a new admin.
     *
     * @param \App\DataTransferObjects\Models\AdminModelData $adminModelData
     * @param string|null $loginUrl
     * @return \App\Models\Admin
     */
    public function create(AdminModelData $adminModelData, string|null $loginUrl = null): Admin
    {
        $country = $adminModelData->getCountryId()
            ? Country::query()->select('id')->where('id', $adminModelData->getCountryId())->firstOrFail()
            : null;

        $password = Str::random(8);

        $admin = Admin::query()->create([
            'country_id' => $country?->id,
            'firstname' => $adminModelData->getFirstname(),
            'lastname' => $adminModelData->getLastname(),
            'email' => $adminModelData->getEmail(),
            'password' => Hash::make($password),
        ]);

        event(new Registered($admin, $password, $loginUrl));

        return $admin->withoutRelations()->refresh();
    }

    /**
     * Update the admin.
     *
     * @param \App\Models\Admin $admin
     * @param \App\DataTransferObjects\Models\AdminModelData $adminModelData
     * @return \App\Models\Admin
     */
    public function update(Admin $admin, AdminModelData $adminModelData): Admin
    {
        /** @var \App\Models\Country $adminCountry */
        $adminCountry = Country::query()->select(['id'])->where('id', $admin->country_id)->firstOrFail();

        if ($adminCountry->id !== $adminModelData->getCountryId() && $admin->phone_number) {
            /** @var \App\Models\Country $newCountry */
            $newCountry = Country::query()
                ->select(['id', 'alpha2_code'])
                ->where('id', $adminModelData->getCountryId())
                ->firstOrFail();

            phone($admin->phone_number, $newCountry->alpha2_code)->formatE164();
        }

        $data = [
            'country_id' => $adminModelData->getCountryId() ?? $admin->country_id,
            'firstname' => $adminModelData->getFirstname() ?? $admin->firstname,
            'lastname' => $adminModelData->getLastname() ?? $admin->lastname,
            'email' => $adminModelData->getEmail() ?? $admin->email,
        ];

        $admin->updateOrFail([...$data, ...[
            'email_verified_at' => $data['email'] !== $admin->email ? null : $admin->email_verified_at,
        ]]);

        return $admin->withoutRelations()->refresh();
    }

    /**
     * Toggle role for admin.
     *
     * @param \App\Models\Admin $admin
     * @param string $roleId
     * @return \App\Models\Admin
     */
    public function toggleRole(Admin $admin, string $roleId): Admin
    {
        $role = Role::query()->findOrFail($roleId);

        if ($admin->hasRole($role)) {
            $admin->removeRole($role);
        } else {
            $admin->roles()->sync($role);

            event(new RoleAssigned($admin, $role->name));
        }

        return $admin->refresh();
    }
}

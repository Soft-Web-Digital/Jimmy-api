<?php

declare(strict_types=1);

namespace App\Services\Profile\Admin;

use App\DataTransferObjects\Models\AdminModelData;
use App\Exceptions\ExpectationFailedException;
use App\Models\Admin;
use App\Models\Country;
use Illuminate\Support\Facades\Storage;

class AdminProfileService
{
    /**
     * Update admin profile.
     *
     * @param \App\Models\Admin $admin
     * @param \App\DataTransferObjects\Models\AdminModelData $adminModelData
     * @return \App\Models\Admin
     */
    public function update(Admin $admin, AdminModelData $adminModelData): Admin
    {
        /** @var \App\Models\Country $country */
        $country = Country::query()
            ->select(['alpha2_code', 'dialing_code'])
            ->where('id', $adminModelData->getCountryId() ?? $admin->country_id)
            ->firstOrFail();

        // Upload new avatar
        $avatar = $admin->avatar;
        if ($adminModelData->getAvatar() instanceof \Illuminate\Http\UploadedFile) {
            $path = $adminModelData->getAvatar()
                ->storeAs('avatars', ($admin->id . '.' . $adminModelData->getAvatar()->extension()));

            throw_if($path === false, ExpectationFailedException::class, 'Avatar could not be uploaded');

            $avatar = Storage::url($path);
        }

        // Format phone number
        $phoneNumber = (
            $adminModelData->getPhoneNumber() &&
            $adminModelData->getPhoneNumber() !== $admin->phone_number
        )
            ? phone($adminModelData->getPhoneNumber(), $country->alpha2_code)->formatE164()
            : $admin->phone_number;

        // Update profile
        $data = [
            'country_id' => $adminModelData->getCountryId() ?? $admin->country_id,
            'firstname' => $adminModelData->getFirstname() ?? $admin->firstname,
            'lastname' => $adminModelData->getLastname() ?? $admin->lastname,
            'email' => $adminModelData->getEmail() ?? $admin->email,
            'avatar' => $avatar,
            'phone_number' => $phoneNumber ? str_replace($country->dialing_code, '', $phoneNumber) : null,
            'fcm_tokens' => $adminModelData->getFcmToken()
                ? collect($admin->fcm_tokens)->add($adminModelData->getFcmToken())->unique()->toArray()
                : $admin->fcm_tokens,
        ];

        $admin->updateOrFail([...$data, ...[
            'email_verified_at' => $data['email'] !== $admin->email ? null : $admin->email_verified_at,
        ]]);

        return $admin->withoutRelations()->refresh();
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Profile\User;

use App\DataTransferObjects\Models\UserModelData;
use App\Exceptions\ExpectationFailedException;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    /**
     * Update the user profile.
     *
     * @param \App\Models\User $user
     * @param \App\DataTransferObjects\Models\UserModelData $userModelData
     * @return \App\Models\User
     */
    public function update(User $user, UserModelData $userModelData): User
    {
        /** @var \App\Models\Country $country */
        $country = Country::query()
            ->select(['alpha2_code', 'dialing_code'])
            ->where('id', $userModelData->getCountryId() ?? $user->country_id)
            ->firstOrFail();

        // Upload new avatar
        $avatar = $user->avatar;
        if ($userModelData->getAvatar() instanceof \Illuminate\Http\UploadedFile) {
            $path = $userModelData->getAvatar()
                ->storeAs('avatars', ($user->id . '.' . $userModelData->getAvatar()->extension()));

            throw_if($path === false, ExpectationFailedException::class, 'Avatar could not be uploaded');

            $avatar = Storage::url($path);
        }

        // Format phone number
        $phoneNumber = (
            $userModelData->getPhoneNumber() &&
            $userModelData->getPhoneNumber() !== $user->phone_number
        )
            ? phone($userModelData->getPhoneNumber(), $country->alpha2_code)->formatE164()
            : $user->phone_number;

        // Update profile
        $data = [
            'country_id' => $userModelData->getCountryId() ?? $user->country_id,
            'firstname' => $userModelData->getFirstname() ?? $user->firstname,
            'lastname' => $userModelData->getLastname() ?? $user->lastname,
            'email' => $userModelData->getEmail() ?? $user->email,
            'email_verified_at' => $user->isDirty('email') ? null : $user->email_verified_at,
            'avatar' => $avatar,
            'username' => $userModelData->getUsername() ?? $user->username,
            'phone_number' => $phoneNumber ? str_replace($country->dialing_code, '', $phoneNumber) : null,
            'date_of_birth' => $userModelData->getDateOfBirth() ?? $user->date_of_birth,
            'fcm_tokens' => $userModelData->getFcmToken()
                ? collect($user->fcm_tokens)->add($userModelData->getFcmToken())->unique()->toArray() // @phpstan-ignore-line
                : $user->fcm_tokens,
        ];
        $user->updateOrFail([...$data, ...[
            'email_verified_at' => $data['email'] !== $user->email ? null : $user->email_verified_at,
        ]]);

        return $user->withoutRelations()->refresh();
    }

    /**
     * Delete user account.
     *
     * @param \App\Models\User $user
     * @param string $reason
     * @return void
     */
    public function delete(User $user, string $reason): void
    {
        DB::transaction(fn () => $user->delete($reason));
    }
}

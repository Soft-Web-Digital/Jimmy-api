<?php

declare(strict_types=1);

namespace App\Services\Profile\User;

use App\DataTransferObjects\Auth\AuthenticationCredentials;
use App\DataTransferObjects\Models\UserModelData;
use App\Enums\SystemDataCode;
use App\Models\Country;
use App\Models\SystemData;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user.
     *
     * @param \App\DataTransferObjects\Models\UserModelData $userModelData
     * @param bool $authenticate
     * @return \App\Models\User|\App\DataTransferObjects\Auth\AuthenticationCredentials
     */
    public function create(UserModelData $userModelData, bool $authenticate = false): User|AuthenticationCredentials
    {
        /** @var \App\Models\Country $country */
        $country = Country::query()
            ->select(['id', 'alpha2_code', 'dialing_code'])
            ->where('id', $userModelData->getCountryId())
            ->firstOrFail();

        /** @var \App\Models\User $user */
        $user = User::query()->create([
            'country_id' => $country->id,
            'firstname' => $userModelData->getFirstname(),
            'lastname' => $userModelData->getLastname(),
            'email' => $userModelData->getEmail(),
            'password' => $userModelData->getPassword() ? Hash::make($userModelData->getPassword()) : null,
            'username' => $userModelData->getUsername(),
            'phone_number' => $userModelData->getPhoneNumber()
                ? str_replace(
                    $country->dialing_code,
                    '',
                    phone($userModelData->getPhoneNumber(), $country->alpha2_code)->formatE164()
                )
                : null,
            'date_of_birth' => $userModelData->getDateOfBirth(),
            'transaction_pin_activated_at' => now(),
        ])->refresh();

        if ($userModelData->getRefCode()) {
            $ref = User::query()->where('ref_code', $userModelData->getRefCode())->first();
            $systemData = SystemData::query()->where('code', 'REFRA')->first();
            $ref->referrals()->create([
                'referred_id' => $user->id,
                'amount' => $systemData?->content ?? SystemDataCode::REFERRAL_REWARD_AMOUNT->defaultContent()
            ]);
        }

        event(new Registered($user));

        return $authenticate
            ? (new AuthenticationCredentials())
                ->setUser($user)
                ->setApiMessage('User created successfully')
                ->setToken($user->createToken($user->getMorphClass())->plainTextToken)
            : $user;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Contracts\HasKyc;
use App\Enums\KycAttribute;
use App\Exceptions\NotAllowedException;
use App\Models\UserKyc;

class UserKycService
{
    /**
     * Verify the KYC.
     *
     * @param \App\Contracts\HasKyc $user
     * @param \App\Enums\KycAttribute $type
     * @param string $record
     * @return \App\Models\UserKyc
     */
    public function verify(HasKyc $user, KycAttribute $type, string $record): UserKyc
    {
        if (!in_array($type, KycAttribute::serviced())) {
            throw new NotAllowedException($type->name . ' service is currently unavailable');
        }

        /** @var \App\Models\UserKyc $kyc */
        $kyc = $user->kyc()->updateOrCreate(
            [
                'user_id' => $user->id,
                'user_type' => $user->getMorphClass(),
            ],
            [
                $type->value => $record,
            ]
        );

        if ($kyc->wasChanged($type->value)) {
            $kyc->verify($type, false);
        }

        $kyc->refresh();

        $verified = "{$type->value}_verified_at";
        if ($kyc->$verified) {
            throw new NotAllowedException($type->name . ' has already been verified.');
        }

        // begin the verification process
        $job = $type->verificationJobClass($user, $record);
        if (!($job instanceof \Exception)) {
            dispatch($job);
        }

        return $kyc;
    }
}

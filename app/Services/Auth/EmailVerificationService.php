<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Auth\MustVerifyEmail;
use App\Exceptions\NotAllowedException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    /**
     * Verify the email.
     *
     * @param \App\Contracts\Auth\MustVerifyEmail $user
     * @param string $code
     * @return void
     */
    public function verify(MustVerifyEmail $user, string $code): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new NotAllowedException('User email already verified');
        }

        /** @var \App\Models\EmailVerificationCode $emailVerificationCode */
        $emailVerificationCode = $user->emailVerificationCode()->first();

        if (!$emailVerificationCode || !Hash::check($code, $emailVerificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => [trans('auth.code.invalid')],
            ]);
        }

        if ($emailVerificationCode->isExpired()) {
            throw ValidationException::withMessages([
                'code' => [trans('auth.code.expired')],
            ]);
        }

        activity()->disableLogging();

        $user->markEmailAsVerified();

        $emailVerificationCode->delete();

        event(new Verified($user));
    }

    /**
     * Resend the email verification token.
     *
     * @param \App\Contracts\Auth\MustVerifyEmail $user
     * @return void
     */
    public function resend(MustVerifyEmail $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new NotAllowedException('User email already verified');
        }

        $user->sendEmailVerificationNotification();
    }
}

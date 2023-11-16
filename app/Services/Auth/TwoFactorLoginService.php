<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Auth\MustSatisfyTwoFa;
use App\DataTransferObjects\Auth\AuthenticationCredentials;
use App\Exceptions\NotAllowedException;
use Illuminate\Foundation\Auth\User as Authenticable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorLoginService
{
    /**
     * Verify user for complete login.
     *
     * @param \App\Contracts\Auth\MustSatisfyTwoFa&\Illuminate\Foundation\Auth\User $user
     * @param string $code
     * @return \App\DataTransferObjects\Auth\AuthenticationCredentials
     */
    public function verify(MustSatisfyTwoFa&Authenticable $user, string $code): AuthenticationCredentials
    {
        if ($user->tokenCan('*')) { // @phpstan-ignore-line
            throw new NotAllowedException('You have completed your authentication already');
        }

        /** @var \App\Models\TwoFaVerificationCode $twoFaVerificationCode */
        $twoFaVerificationCode = $user->twoFaVerificationCode()->first();

        if (!$twoFaVerificationCode || !Hash::check($code, $twoFaVerificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => [trans('auth.code.invalid')],
            ]);
        }

        if ($twoFaVerificationCode->isExpired()) {
            throw ValidationException::withMessages([
                'code' => [trans('auth.code.expired')],
            ]);
        }

        $twoFaVerificationCode->deleteOrFail();

        // @phpstan-ignore-next-line
        $user->currentAccessToken()->delete();

        return (new AuthenticationCredentials())
            ->setUser($user)
            ->setApiMessage(trans('auth.success'))
            ->setToken($user->createToken($user->getMorphClass(), ['*'])->plainTextToken); // @phpstan-ignore-line
    }

    /**
     * Regenerate a new Two-Fa code and send to the user.
     *
     * @param \App\Contracts\Auth\MustSatisfyTwoFa&\Illuminate\Foundation\Auth\User $user
     * @return void
     */
    public function resend(MustSatisfyTwoFa&Authenticable $user): void
    {
        if ($user->tokenCan('*')) { // @phpstan-ignore-line
            throw new NotAllowedException('You have completed your authentication already');
        }

        $user->sendTwoFaNotification();
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ExpectationFailedException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordService
{
    /**
     * Request a reset code for the user.
     *
     * @param string $email
     * @param string $broker
     * @return string
     */
    public function request(string $email, string $broker): string
    {
        $status = Password::broker($broker)->sendResetLink([
            'email' => $email
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new ExpectationFailedException(trans($status));
        }

        return trans(Password::RESET_LINK_SENT);
    }

    /**
     * Verify the reset code.
     *
     * @param string $email
     * @param string $code
     * @param string $broker
     * @return string
     */
    public function verify(string $email, string $code, string $broker): string
    {
        /** @var \App\Auth\Passwords\PasswordBroker $status */
        $status = Password::broker($broker);

        $status = $status->validateReset([
            'email' => $email,
            'token' => $code,
        ]);

        if (!$status instanceof CanResetPassword) {
            throw new ExpectationFailedException(trans($status));
        }

        return trans('passwords.valid_token');
    }

    /**
     * Reset the user's password.
     *
     * @param string $email
     * @param string $password
     * @param string $code
     * @param string $broker
     * @return string
     */
    public function reset(string $email, string $password, string $code, string $broker): string
    {
        activity()->disableLogging();

        $status = Password::broker($broker)->reset(
            [
                'email' => $email,
                'password' => $password,
                'token' => $code,
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);

                if (isset($user->password_unprotected) && $user->password_unprotected) {
                    $user->password_unprotected = false;
                }

                $user->saveOrFail();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ExpectationFailedException(trans($status));
        }

        return trans($status);
    }
}

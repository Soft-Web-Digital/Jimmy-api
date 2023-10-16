<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DataTransferObjects\Auth\AuthenticationCredentials;
use App\Exceptions\AccountDeletedPermanentlyException;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AppleOAuthService
{
    /**
     * Authenticate with Apple.
     *
     * @param string $appleUserToken
     * @return \App\DataTransferObjects\Auth\AuthenticationCredentials
     */
    public function authenticate(string $appleUserToken)
    {
        $response = Socialite::driver('sign-in-with-apple')->userFromToken($appleUserToken); // @phpstan-ignore-line
        if (!$response) {
            throw ValidationException::withMessages([
                'user_token' => [trans('auth.service_failed')],
            ]);
        }
        $user = User::withTrashed()->where('email', $response->email)->first();
        if (!$user) {
            $user = User::create([
                'country_id' => Country::query()->where('alpha2_code', 'NG')->valueOrFail('id'),
                'firstname' => $response->user['name']['firstName'] ?? 'null',
                'lastname' => $response->user['name']['lastName'] ?? 'null',
                'email' => $response->email,
                'password' => Hash::make(uniqid()),
                'avatar' => $response->avatar,
                'email_verified_at' => $response->user['email_verified'] ? now() : null
            ])->refresh();
        }

        if ($user->trashed()) {
            throw new AccountDeletedPermanentlyException();
        }

        return (new AuthenticationCredentials())
            ->setUser($user)
            ->setApiMessage(trans('auth.success'))
            ->setToken($user->createToken($user->getMorphClass(), ['*'])->plainTextToken);
    }
}

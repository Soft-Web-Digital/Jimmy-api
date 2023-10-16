<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DataTransferObjects\Auth\AuthenticationCredentials;
use App\Exceptions\AccountDeletedPermanentlyException;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleOAuthService
{
    private const API_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /**
     * Authenticate with Google.
     *
     * @param string $googleUserToken
     * @return \App\DataTransferObjects\Auth\AuthenticationCredentials
     */
    public function authenticate(string $googleUserToken)
    {
        $response = Http::withToken($googleUserToken)->get(self::API_URL);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'user_token' => [trans('auth.service_failed')],
            ]);
        }

        $responseData = $response->json();

        $user = User::withTrashed()->where('email', $responseData['email'])->first();

        if (!$user) {
            $user = User::query()->create([
                'country_id' => Country::query()->where('alpha2_code', 'NG')->valueOrFail('id'),
                'firstname' => $responseData['given_name'],
                'lastname' => $responseData['family_name'] ?? $responseData['given_name'],
                'email' => $responseData['email'],
                'password' => Hash::make(uniqid()),
                'avatar' => $responseData['picture'] ?? null,
                'email_verified_at' => (bool) ($responseData['email_verified'] ?? null) ? now() : null,
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

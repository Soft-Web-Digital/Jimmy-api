<?php

namespace App\Http\Controllers\User\Auth;

use App\Exceptions\NotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\SocialAuthRequest;
use App\Services\Auth\AppleOAuthService;
use App\Services\Auth\GoogleOAuthService;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class SocialAuthController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \App\Http\Requests\User\Auth\SocialAuthRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(SocialAuthRequest $request): Response
    {
        $authenticationCredentials = match ($request->channel) {
            'google' => (new GoogleOAuthService())->authenticate($request->user_token),
            'apple' => (new AppleOAuthService())->authenticate($request->user_token),
            default => throw new NotAllowedException('Channel not configured.')
        };

        return ResponseBuilder::asSuccess()
            ->withMessage($authenticationCredentials->getApiMessage())
            ->withData([
                'user' => $authenticationCredentials->getUser(),
                'token' => $authenticationCredentials->getToken(),
                'requires_two_fa' => $authenticationCredentials->getTwoFaRequired(),
            ])
            ->build();
    }
}

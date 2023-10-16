<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\TwoFactorLoginRequest;
use App\Services\Auth\TwoFactorLoginService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Services\Auth\TwoFactorLoginService $twoFactorLoginService
     */
    public function __construct(private TwoFactorLoginService $twoFactorLoginService)
    {
    }

    /**
     * Verify the Two-FA Code and complete login.
     *
     * @param \App\Http\Requests\User\Auth\TwoFactorLoginRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function verify(TwoFactorLoginRequest $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user('api_user');

        $authenticationCredentials = $this->twoFactorLoginService->verify($user, $request->code);

        return ResponseBuilder::asSuccess()
            ->withMessage($authenticationCredentials->getApiMessage())
            ->withData([
                'user' => $authenticationCredentials->getUser(),
                'token' => $authenticationCredentials->getToken(),
            ])
            ->build();
    }

    /**
     * Regenerate and send a new Two-FA code for user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resend(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user('api_user');

        $this->twoFactorLoginService->resend($user);

        return ResponseBuilder::asSuccess()
            ->withMessage('Two-Fa Code Resent Successfully')
            ->build();
    }
}

<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\Admin;
use App\Services\Auth\LoginService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Services\Auth\LoginService $loginService
     */
    public function __construct(private LoginService $loginService)
    {
    }

    /**
     * Start authenticated session.
     *
     * @param \App\Http\Requests\Admin\Auth\LoginRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function login(LoginRequest $request): Response
    {
        $authenticationCredentials = $this->loginService->start(
            'email',
            $request->email,
            $request->password,
            (new Admin())
        );

        return ResponseBuilder::asSuccess()
            ->withMessage($authenticationCredentials->getApiMessage())
            ->withData([
                'admin' => $authenticationCredentials->getUser(),
                'token' => $authenticationCredentials->getToken(),
                'requires_two_fa' => $authenticationCredentials->getTwoFaRequired(),
            ])
            ->build();
    }

    /**
     * End authenticated session.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logout(Request $request): Response
    {
        $this->loginService->stop($request->user());

        return ResponseBuilder::asSuccess()
            ->withMessage('Logout was successful.')
            ->build();
    }

    /**
     * End authenticated sessions on other devices, except the current one.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logoutOtherDevices(Request $request): Response
    {
        $this->loginService->stopOthers($request->user());

        return ResponseBuilder::asSuccess()
            ->withMessage('All other devices have been logged-out successfully.')
            ->build();
    }
}

<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ResetPasswordRequest;
use App\Http\Requests\Admin\Auth\SendPasswordResetRequest;
use App\Http\Requests\Admin\Auth\VerifyPasswordResetCodeRequest;
use App\Services\Auth\ResetPasswordService;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Services\Auth\ResetPasswordService $resetPasswordService
     */
    public function __construct(private ResetPasswordService $resetPasswordService)
    {
    }

    /**
     * Request for a reset code.
     *
     * @param \App\Http\Requests\Admin\Auth\SendPasswordResetRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgot(SendPasswordResetRequest $request): Response
    {
        $response = $this->resetPasswordService->request($request->email, 'admins');

        return ResponseBuilder::asSuccess()
            ->withMessage($response)
            ->build();
    }

    /**
     * Verify the reset code.
     *
     * @param \App\Http\Requests\Admin\Auth\VerifyPasswordResetCodeRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function verify(VerifyPasswordResetCodeRequest $request): Response
    {
        $response = $this->resetPasswordService->verify($request->email, $request->code, 'admins');

        return ResponseBuilder::asSuccess()
            ->withMessage($response)
            ->build();
    }

    /**
     * Reset the user's password.
     *
     * @param \App\Http\Requests\Admin\Auth\ResetPasswordRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reset(ResetPasswordRequest $request): Response
    {
        $response = $this->resetPasswordService->reset($request->email, $request->password, $request->code, 'admins');

        return ResponseBuilder::asSuccess()
            ->withMessage($response)
            ->build();
    }
}

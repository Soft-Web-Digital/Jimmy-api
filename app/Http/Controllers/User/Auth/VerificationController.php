<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\EmailVerificationRequest;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param \App\Services\Auth\EmailVerificationService $emailVerificationService
     */
    public function __construct(private EmailVerificationService $emailVerificationService)
    {
    }

    /**
     * Verify the email.
     *
     * @param \App\Http\Requests\User\Auth\EmailVerificationRequest $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function verify(EmailVerificationRequest $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->emailVerificationService->verify($user, $request->code);

        return ResponseBuilder::asSuccess()
            ->withMessage('Email verified successfully')
            ->build();
    }

    /**
     * Resend the email verification token.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resend(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->emailVerificationService->resend($user);

        return ResponseBuilder::asSuccess()
            ->withMessage('Email verification code resent successfully')
            ->build();
    }
}

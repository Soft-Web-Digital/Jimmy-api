<?php

namespace App\Contracts\Auth;

use App\DataTransferObjects\Models\EmailVerificationCodeModelData;
use Illuminate\Contracts\Auth\MustVerifyEmail as AuthMustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface MustVerifyEmail extends AuthMustVerifyEmail
{
    /**
     * Generate code for email verification.
     *
     * @return \App\DataTransferObjects\Models\EmailVerificationCodeModelData
     */
    public function generateEmailVerificationCodeModel(): EmailVerificationCodeModelData;

    /**
     * Email verification code associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function emailVerificationCode(): MorphOne;

    /**
     * Generate a random code.
     *
     * @return string
     */
    public function generateEmailVerificationCode(): string;
}

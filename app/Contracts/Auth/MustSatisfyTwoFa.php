<?php

namespace App\Contracts\Auth;

use App\DataTransferObjects\Models\TwoFaVerificationCodeModelData;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface MustSatisfyTwoFa
{
    /**
     * Generate code for two-factor authentication.
     *
     * @return \App\DataTransferObjects\Models\TwoFaVerificationCodeModelData
     */
    public function generateTwoFaVerificationCodeModel(): TwoFaVerificationCodeModelData;

    /**
     * Generate a random code.
     *
     * @return string
     */
    public function generateTwoFaVerificationCode(): string;

    /**
     * The user's two-factor verification code model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function twoFaVerificationCode(): MorphOne;

    /**
     * Send the two-factor authentication notification.
     *
     * @return void
     */
    public function sendTwoFaNotification();

    /**
     * Toggle two_fa_activated_at column.
     *
     * @return void
     */
    public function toggleTwoFaActivation(): void;
}

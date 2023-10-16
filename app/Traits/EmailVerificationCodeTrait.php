<?php

namespace App\Traits;

use App\DataTransferObjects\Models\EmailVerificationCodeModelData;
use App\Models\EmailVerificationCode;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait EmailVerificationCodeTrait
{
    /**
     * Generate code for email verification.
     *
     * @return \App\DataTransferObjects\Models\EmailVerificationCodeModelData
     */
    public function generateEmailVerificationCodeModel(): EmailVerificationCodeModelData
    {
        DB::beginTransaction();

        try {
            $this->emailVerificationCode()->delete();

            $emailVerificationCode = new EmailVerificationCode();
            $emailVerificationCode->user()->associate($this);

            $code = $this->generateEmailVerificationCode();

            $emailVerificationCode->code = Hash::make($code);
            $emailVerificationCode->saveOrFail();

            DB::commit();

            return (new EmailVerificationCodeModelData())->setUser($this)->setCode($code);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Email verification code associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function emailVerificationCode(): MorphOne
    {
        return $this->morphOne(EmailVerificationCode::class, 'user');
    }

    /**
     * Generate a random code.
     *
     * @return string
     */
    public function generateEmailVerificationCode(): string
    {
        return (string) mt_rand(100000, 999999);
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\Auth\VerifyEmailNotification(
            $this->generateEmailVerificationCodeModel()->getCode()
        ));
    }
}

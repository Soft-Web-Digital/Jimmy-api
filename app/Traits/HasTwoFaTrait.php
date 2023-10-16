<?php

namespace App\Traits;

use App\DataTransferObjects\Models\TwoFaVerificationCodeModelData;
use App\Models\TwoFaVerificationCode;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait HasTwoFaTrait
{
    /**
     * Generate Two-FA Code
     *
     * @return \App\DataTransferObjects\Models\TwoFaVerificationCodeModelData
     */
    public function generateTwoFaVerificationCodeModel(): TwoFaVerificationCodeModelData
    {
        DB::beginTransaction();

        try {
            $this->twoFaVerificationCode()->delete();

            $twoFaVerificationCode = new TwoFaVerificationCode();
            $twoFaVerificationCode->user()->associate($this);

            $code = $this->generateTwoFaVerificationCode();

            $twoFaVerificationCode->code = Hash::make($code);
            $twoFaVerificationCode->ip_address = request()->ip();
            $twoFaVerificationCode->saveOrFail();

            DB::commit();

            return (new TwoFaVerificationCodeModelData())
                ->setUser($this)
                ->setCode($code)
                ->setIpAddress($twoFaVerificationCode->ip_address);
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
    public function twoFaVerificationCode(): MorphOne
    {
        return $this->morphOne(TwoFaVerificationCode::class, 'user');
    }

    /**
     * Toggle two_fa_activated_at column.
     *
     * @return void
     */
    public function toggleTwoFaActivation(): void
    {
        $this->two_fa_activated_at = $this->two_fa_activated_at ? null : now();
        $this->saveOrFail();
    }

    /**
     * Generate a random code.
     *
     * @return string
     */
    public function generateTwoFaVerificationCode(): string
    {
        return (string) mt_rand(100000, 999999);
    }

    /**
     * Send the two-factor authentication notification.
     *
     * @return void
     */
    public function sendTwoFaNotification(): void
    {
        $twoFaVerificationModelData = $this->generateTwoFaVerificationCodeModel();

        $this->notifyNow(new \App\Notifications\Auth\TwofaNotification(
            $twoFaVerificationModelData->getCode(),
            $twoFaVerificationModelData->getIpAddress()
        ));
    }
}

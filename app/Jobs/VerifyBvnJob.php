<?php

namespace App\Jobs;

use App\Contracts\CanVerifyBvn;
use App\Contracts\HasKyc;
use App\Enums\KycAttribute;
use App\Enums\Queue;
use App\Exceptions\IncompleteDataException;
use App\Notifications\User\IncompleteDataNotification;
use App\Notifications\User\KycVerificationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerifyBvnJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param \App\Contracts\HasKyc $user
     * @param string $bvn
     * @return void
     */
    public function __construct(public HasKyc $user, public string $bvn)
    {
        $this->onQueue(Queue::CRITICAL->value);
    }

    /**
     * Execute the job.
     *
     * @param \App\Contracts\CanVerifyBvn $canVerifyBvn
     * @return void
     */
    public function handle(CanVerifyBvn $canVerifyBvn)
    {
        $user = $this->user;

        $propertyRequirements = $canVerifyBvn::PROPERTYREQUIREMENTS;

        try {
            foreach ($propertyRequirements as $property) {
                if (!isset($user->$property)) {
                    throw new IncompleteDataException(
                        'Kindly provide us with your '
                        . Str::of($property)->lower()->headline()
                        . ' to complete your BVN verification process.'
                    );
                }
            }

            if (!in_array(strtolower($user->country->alpha2_code), $canVerifyBvn::BVNVERIFIABLECOUNTRIES)) {
                return;
            }

            $verified = $canVerifyBvn->verify(
                $this->bvn,
                $user->date_of_birth,
                phone($user->phone_number, $user->country->alpha2_code),
                $user->firstname,
                $user->lastname
            );

            if ($verified) {
                $user->verify(KycAttribute::BVN, true);
                $user->notify(new KycVerificationNotification(KycAttribute::BVN, true));
            } else {
                $user->notify(new KycVerificationNotification(KycAttribute::BVN, false));
            }
        } catch (\App\Exceptions\IncompleteDataException $e) {
            $user->notify(new IncompleteDataNotification($e->getMessage()));
        } catch (\Exception $e) {
            $user->notify(new KycVerificationNotification(KycAttribute::BVN, false));

            Log::emergency($e->getMessage());
        }
    }
}

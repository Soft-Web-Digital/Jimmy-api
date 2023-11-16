<?php

namespace App\Services\VerifiedAfrica;

use App\Contracts\CanVerifyBvn;
use App\Exceptions\ExpectationFailedException;
use Carbon\Carbon;
use Propaganistas\LaravelPhone\PhoneNumber;

class VerifiedAfricaBvnService extends VerifiedAfricaBaseService implements CanVerifyBvn
{
    /**
     * List of country alpha2 codes.
     *
     * @var array<int, string>
     */
    public const BVNVERIFIABLECOUNTRIES = [
        'ng',
    ];

    public const PROPERTYREQUIREMENTS = [
        'firstname',
        'lastname',
        'date_of_birth',
        'phone_number',
    ];

    public const VERIFICATIONTYPE = 'BVN-BOOLEAN-MATCH';

    /**
     * Verify BVN.
     *
     * @param string $bvn
     * @param \Carbon\Carbon $dateOfBirth
     * @param \Propaganistas\LaravelPhone\PhoneNumber $phoneNumber
     * @param string $firstName
     * @param string $lastName
     * @return bool
     */
    public function verify(
        string $bvn,
        Carbon $dateOfBirth,
        PhoneNumber $phoneNumber,
        string $firstName,
        string $lastName
    ): bool {
        $phoneNumber = str_replace(' ', '', $phoneNumber->formatNational());

        $verifyRequest = $this->connection()->post('/', [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phoneNumber,
            'searchParameter' => $bvn,
            'dob' => $dateOfBirth->isoFormat('D-MMM-YYYY'),
            'verificationType' => self::VERIFICATIONTYPE,
        ]);

        $verifyResponse = $verifyRequest->object();

        info('verified africa', [$verifyResponse]);

        if ($verifyRequest->failed()) {
            throw new ExpectationFailedException('Verified Africa BVN Verifier Service failed');
        }

        return $verifyResponse->response->status == '00';
    }
}

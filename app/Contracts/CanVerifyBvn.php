<?php

namespace App\Contracts;

use Carbon\Carbon;
use Propaganistas\LaravelPhone\PhoneNumber;

interface CanVerifyBvn
{
    /**
     * List of country alpha2 codes.
     *
     * @var array<int, string>
     */
    public const BVNVERIFIABLECOUNTRIES = [];

    public const PROPERTYREQUIREMENTS = [];

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
    ): bool;
}

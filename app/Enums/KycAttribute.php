<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\HasKyc;
use App\Jobs\VerifyBvnJob;
use App\Traits\EnumTrait;

enum KycAttribute: string
{
    use EnumTrait;

    case BVN = 'bvn';
    case NIN = 'nin';

    /**
     * Get the serviced attributes.
     * If the attribute has a service to complete verification.
     *
     * @return array<int, self>
     */
    public static function serviced(): array
    {
        return [
            self::BVN,
        ];
    }

    /**
     * Get the job class to initiate the verification.
     *
     * @param \App\Contracts\HasKyc $user
     * @param string $value
     * @return object
     */
    public function verificationJobClass(HasKyc $user, string $value): object
    {
        return match ($this) {
            self::BVN => new VerifyBvnJob($user, $value),
            default => throw new \App\Exceptions\ExpectationFailedException('Verification is unavailable'),
        };
    }
}

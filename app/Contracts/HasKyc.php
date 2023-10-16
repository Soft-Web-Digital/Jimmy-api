<?php

namespace App\Contracts;

use App\Enums\KycAttribute;

/**
 * @property-read string $id
 * @property-read string $firstname
 * @property-read string $lastname
 * @property-read \App\Models\Country|object|null $country
 * @property-read string|null $phone_number
 * @property-read \Carbon\Carbon|string|null $date_of_birth
 * @method string getMorphClass()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo country()
 * @method void notify($instance)
 */
interface HasKyc
{
    /**
     * Mark as verified
     *
     * @param \App\Enums\KycAttribute $type
     * @param bool $verified
     * @return void
     */
    public function verify(KycAttribute $type, bool $verified = true): void;

    /**
     * Get the KYC.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function kyc(): \Illuminate\Database\Eloquent\Relations\MorphOne;

    /**
     * Get the country.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo;
}

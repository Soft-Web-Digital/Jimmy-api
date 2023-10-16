<?php

declare(strict_types=1);

namespace App\Services\RestCountries;

use App\Contracts\Fillers\CountryFiller;
use App\Models\Country;
use Illuminate\Support\Str;

class RestCountriesCountryService extends RestCountriesBaseService implements CountryFiller
{
    /**
     * Fill the database with countries.
     *
     * @return void
     */
    public function fillCountriesInStorage(): void
    {
        $restCountriesApi = $this->connection()->get('/v3.1/all?fields=name,cca2,cca3,flags,idd');

        if ($restCountriesApi->successful()) {
            $restCountries = $restCountriesApi->collect()->map(function ($country) {
                return [
                    'id' => Str::orderedUuid()->toString(),
                    'name' => $this->getCommonName($country['name']['common']),
                    'alpha2_code' => strtoupper($country['cca2']),
                    'alpha3_code' => strtoupper($country['cca3']),
                    'flag_url' => $country['flags']['png'] ?? $country['flags']['svg'],
                    'dialing_code' => $country['idd']['root']
                        . (count($country['idd']['suffixes']) === 1 ? $country['idd']['suffixes'][0] : ''),
                    'registration_activated_at' => now(),
                ];
            })->toArray();

            Country::query()->upsert(
                $restCountries,
                ['alpha3_code'],
                ['alpha2_code', 'name', 'flag_url', 'dialing_code']
            );
        }
    }

    /**
     * Get the proper country name.
     *
     * @param string $name
     * @return string
     */
    private function getCommonName(string $name): string
    {
        return match (strtolower($name)) {
            'united states' => 'United States of America',
            default => $name,
        };
    }
}

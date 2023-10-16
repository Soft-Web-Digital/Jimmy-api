<?php

declare(strict_types=1);

namespace App\Services\Paystack;

use App\Contracts\Fillers\BankFiller;
use App\Models\Bank;
use App\Models\Country;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Yabacon\Paystack\Helpers\Router;

class PaystackBankService implements BankFiller
{
    public const SUPPORTEDCOUNTRIES = [
        'nigeria',
    ];

    /**
     * Fill the database with banks.
     *
     * @return void
     */
    public function fillBanksInStorage(): void
    {
        $supportedCountries = Country::query()
            ->select(['id', 'name'])
            ->whereIn('name', self::SUPPORTEDCOUNTRIES)
            ->get();

        $paystackBanks = Http::withToken(config('paystack.secret_key'))
            ->pool(fn (\Illuminate\Http\Client\Pool $pool) => $this->buildPoolConnections($pool, $supportedCountries));

        $banks = collect();

        foreach ($supportedCountries as $country) {
            if (!$paystackBanks[$country->id] instanceof \Exception) {
                if ($paystackBanks[$country->id]->ok() && $paystackBanks[$country->id]->object()->status) {
                    $banks->push(
                        collect($paystackBanks[$country->id]->object()->data) // @phpstan-ignore-line
                            ->filter(fn ($country) => !empty(trim($country->code)))
                            ->map(function ($bank) use ($country) {
                                return [
                                    'id' => str()->orderedUuid()->toString(),
                                    'country_id' => $country->id,
                                    'code' => $bank->code,
                                    'name' => $bank->name,
                                ];
                            })
                            ->toArray()
                    );
                }
            }
        }

        Bank::query()->upsert($banks->collapse()->toArray(), ['country_id', 'name'], ['name', 'code']);
    }

    /**
     * Build pool connection.
     *
     * @param \Illuminate\Http\Client\Pool $pool
     * @param \Illuminate\Support\Collection $countries
     * @return array<int, \Illuminate\Http\Client\Response>
     */
    private function buildPoolConnections(Pool $pool, Collection $countries): array
    {
        $pools = [];

        foreach ($countries as $country) {
            $pools[] = $pool->as($country->id)->get(
                Router::PAYSTACK_API_ROOT . '/bank?country=' . strtolower($country->name)
            );
        }

        return $pools;
    }
}

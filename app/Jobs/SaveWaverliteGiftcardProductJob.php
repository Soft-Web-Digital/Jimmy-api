<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\GiftcardServiceProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveWaverliteGiftcardProductJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param object $product
     * @return void
     */
    public function __construct(protected object $product)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = date('Y-m-d H:i:s');

        // Get the category
        $categoryName = $this->product->category;
        $giftcardCategoryId = DB::table('giftcard_categories')
            ->where('service_provider', GiftcardServiceProvider::WAVERLITE->value)
            ->where('name', $categoryName)
            ->value('id');

        if (!$giftcardCategoryId) {
            return;
        }

        $productCountries = collect($this->product->countries)->pluck('name'); // @phpstan-ignore-line
        $countries = DB::table('countries')
            ->select(['id', 'name'])
            ->whereIn('name', $productCountries)
            ->get();

        $productCurrencies = collect($this->product->countries)->pluck('currency'); // @phpstan-ignore-line
        $currencies = DB::table('currencies')
            ->select(['id', 'code'])
            ->whereIn('code', collect($productCurrencies)->pluck('code'))
            ->get();

        $giftcardCategoryCountries = [];
        $giftcardProducts = [];

        foreach ($this->product->countries as $country) {
            $countryId = $countries->where('name', $country->name)->value('id');
            if (!$countryId) {
                continue;
            }

            // Sync the giftcard category & country
            $giftcardCategoryCountries[] = [
                'giftcard_category_id' => $giftcardCategoryId,
                'country_id' => $countryId,
            ];

            $currencyId = $currencies->where('code', $country->currency->code)->value('id');
            if (!$currencyId) {
                continue;
            }

            foreach ($country->products->denominations as $product) {
                $giftcardProducts[] = [
                    'id' => Str::orderedUuid()->toString(),
                    'giftcard_category_id' => $giftcardCategoryId,
                    'country_id' => $countryId,
                    'currency_id' => $currencyId,
                    'name' => $this->product->name,
                    'buy_min_amount' => $product->min,
                    'buy_max_amount' => $product->max,
                    'service_provider' => GiftcardServiceProvider::WAVERLITE->value,
                    'service_provider_reference' => $product->identifier,
                    'activated_at' => $now,
                ];
            }
        }

        // Save the giftcard category countries
        DB::table('giftcard_category_country')->upsert($giftcardCategoryCountries, [
            'giftcard_category_id',
            'country_id',
        ]);

        // Save the giftcard products
        DB::table('giftcard_products')->upsert($giftcardProducts, [
            'giftcard_category_id',
            'country_id',
            'currency_id',
            'name',
        ]);
    }
}

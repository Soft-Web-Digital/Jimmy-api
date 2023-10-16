<?php

namespace App\Models;

use App\Enums\GiftcardServiceProvider;
use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftcardProduct extends Model
{
    use HasFactory;
    use UUID;
    use MorphMapTrait;
    use ActivityLogTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'giftcard_category_id',
        'country_id',
        'currency_id',
        'name',
        'sell_rate',
        'sell_min_amount',
        'sell_max_amount',
        'buy_min_amount',
        'buy_max_amount',
        'service_provider',
        'service_provider_reference',
        'activated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        //
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sell_rate' => 'float',
        'sell_min_amount' => 'float',
        'sell_max_amount' => 'float',
        'buy_min_amount' => 'float',
        'buy_max_amount' => 'float',
        'service_provider' => GiftcardServiceProvider::class,
        'activated_at' => 'datetime',
    ];

    /**
     * The columns that are selectable by a query builder.
     *
     * @return array<int, string>
     */
    public function getQuerySelectables(): array
    {
        $table = $this->getTable();

        return [
            "{$table}.id",
            "{$table}.giftcard_category_id",
            "{$table}.country_id",
            "{$table}.currency_id",
            "{$table}.name",
            "{$table}.sell_rate",
            "{$table}.sell_min_amount",
            "{$table}.sell_max_amount",
            "{$table}.buy_min_amount",
            "{$table}.buy_max_amount",
            "{$table}.service_provider",
            "{$table}.service_provider_reference",
            "{$table}.activated_at",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }

    /**
     * Toggle the activated_at column.
     *
     * @return void
     */
    public function toggleActivation(): void
    {
        $this->activated_at = $this->activated_at ? null : now();
        $this->saveOrFail();
    }

    /**
     * Scope a query to filter results based on 'activated_at' status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeActivated($query, bool $active = true): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(
            $active,
            fn ($query) => $query->whereNotNull('activated_at'),
            fn ($query) => $query->whereNull('activated_at')
        );
    }

    /**
     * Get the giftcard category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function giftcardCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GiftcardCategory::class);
    }

    /**
     * Get the country.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the currency.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

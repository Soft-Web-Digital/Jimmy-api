<?php

namespace App\Models;

use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
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
        'code',
        'name',
        'icon',
        'buy_rate',
        'sell_rate',
        'sell_min_amount',
        'sell_max_amount',
        'buy_min_amount',
        'buy_max_amount',
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
        'buy_rate' => 'float',
        'sell_rate' => 'float',
        'sell_min_amount' => 'float',
        'sell_max_amount' => 'float',
        'buy_min_amount' => 'float',
        'buy_max_amount' => 'float',
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
            "{$table}.code",
            "{$table}.name",
            "{$table}.icon",
            "{$table}.buy_rate",
            "{$table}.sell_rate",
            "{$table}.sell_min_amount",
            "{$table}.sell_max_amount",
            "{$table}.buy_min_amount",
            "{$table}.buy_max_amount",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }

    /**
     * Interact with the asset's code.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtoupper($value),
            set: fn ($value) => strtoupper($value),
        );
    }

    /**
     * Scope a query to filter results where network_id is synced.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $networkId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeNetworkId($query, string $networkId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('networks', fn ($query) => $query->where('network_id', $networkId));
    }

    /**
     * Get the networks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function networks(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Network::class);
    }
}

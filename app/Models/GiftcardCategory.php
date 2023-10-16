<?php

namespace App\Models;

use App\Enums\GiftcardServiceProvider;
use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftcardCategory extends Model
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
        'name',
        'icon',
        'sale_term',
        'purchase_term',
        'service_provider',
        'sale_activated_at',
        'purchase_activated_at',
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
        'sale_activated_at' => 'datetime',
        'purchase_activated_at' => 'datetime',
        'service_provider' => GiftcardServiceProvider::class,
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
            "{$table}.name",
            "{$table}.icon",
            "{$table}.sale_term",
            "{$table}.purchase_term",
            "{$table}.service_provider",
            "{$table}.sale_activated_at",
            "{$table}.purchase_activated_at",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function (self $giftcardCategory) {
            $giftcardCategory->giftcardProducts()->delete();
        });
    }

    /**
     * Toggle the sale_activated_at column.
     *
     * @return void
     */
    public function toggleSaleActivation(): void
    {
        $this->sale_activated_at = $this->sale_activated_at ? null : now();
        $this->saveOrFail();
    }

    /**
     * Scope a query to filter results based on 'sale_activated_at' status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeSaleActivated($query, bool $active = true): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(
            $active,
            fn ($query) => $query->whereNotNull('sale_activated_at'),
            fn ($query) => $query->whereNull('sale_activated_at')
        );
    }

    /**
     * Toggle the purchase_activated_at column.
     *
     * @return void
     */
    public function togglePurchaseActivation(): void
    {
        if (!$this->service_provider) {
            throw new \App\Exceptions\NotAllowedException('This category does not have a service provider.');
        }

        $this->purchase_activated_at = $this->purchase_activated_at ? null : now();
        $this->saveOrFail();
    }

    /**
     * Scope a query to filter results based on 'purchase_activated_at' status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePurchaseActivated($query, bool $active = true): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(
            $active,
            fn ($query) => $query->whereNotNull('purchase_activated_at'),
            fn ($query) => $query->whereNull('purchase_activated_at')
        );
    }

    /**
     * Get the countries.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function countries(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'giftcard_category_country');
    }

    /**
     * Get the giftcard products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function giftcardProducts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GiftcardProduct::class);
    }

    /**
     * Get the admins.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function admins(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Admin::class);
    }
}

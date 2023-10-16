<?php

namespace App\Models;

use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
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
        'alpha2_code',
        'alpha3_code',
        'flag_url',
        'dialing_code',
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
        'registration_activated_at' => 'datetime',
        'giftcard_activated_at' => 'datetime',
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
            "{$table}.alpha2_code",
            "{$table}.alpha3_code",
            "{$table}.flag_url",
            "{$table}.dialing_code",
            "{$table}.registration_activated_at",
            "{$table}.giftcard_activated_at",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }

    /**
     * Toggle the registration_activated_at column.
     *
     * @return void
     */
    public function toggleRegistrationActivation(): void
    {
        $this->registration_activated_at = $this->registration_activated_at ? null : now();
        $this->saveOrFail();
    }

    /**
     * Toggle the giftcard_activated_at column.
     *
     * @return void
     */
    public function toggleGiftcardActivation(): void
    {
        $this->giftcard_activated_at = $this->giftcard_activated_at ? null : now();
        $this->saveOrFail();
    }

    /**
     * Scope a query to filter results based on 'registration_activated_at' status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeRegistrationActivated($query, bool $active = true): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(
            $active,
            fn ($query) => $query->whereNotNull('registration_activated_at'),
            fn ($query) => $query->whereNull('registration_activated_at')
        );
    }

    /**
     * Scope a query to filter results based on 'giftcard_activated_at' status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeGiftcardActivated($query, bool $active = true): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(
            $active,
            fn ($query) => $query->whereNotNull('giftcard_activated_at'),
            fn ($query) => $query->whereNull('giftcard_activated_at')
        );
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
}

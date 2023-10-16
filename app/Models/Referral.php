<?php

namespace App\Models;

use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;
    use UUID;
    use MorphMapTrait;
    use ActivityLogTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'referred_id',
        'amount',
        'cumulative_amount',
        'paid',
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
        'paid' => 'boolean',
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
            "{$table}.referee_id",
            "{$table}.referred_id",
            "{$table}.amount",
            "{$table}.paid",
            "{$table}.created_at",
            "{$table}.updated_at",
        ];
    }

    /**
     * Scope a query to filter based on full name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeName(\Illuminate\Database\Eloquent\Builder $query, string $name): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('referred', fn ($q) => $q->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$name}%"]));
    }

    /**
     * Scope a query to filter based on email.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $email
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmail(\Illuminate\Database\Eloquent\Builder $query, string $email): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('referred', fn ($q) => $q->whereRaw('email LIKE ?', ["%{$email}%"]));
    }

    /**
     * Scope a query to filter based on created date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDate(\Illuminate\Database\Eloquent\Builder $query, string $date): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereDate('created_at', $date);
    }

    /**
     * Scope a query to filter based on created date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserId(\Illuminate\Database\Eloquent\Builder $query, string $id): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('referee_id', $id);
    }

    public function referee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function referred(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}

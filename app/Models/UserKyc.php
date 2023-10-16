<?php

namespace App\Models;

use App\Enums\KycAttribute;
use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKyc extends Model
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
        'user_type',
        'user_id',
        'bvn',
        'nin',
        'bvn_verified_at',
        'nin_verified_at',
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
        'bvn_verified_at' => 'datetime',
        'nin_verified_at' => 'datetime',
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
            "{$table}.user_type",
            "{$table}.user_id",
            "{$table}.bvn",
            "{$table}.nin",
            "{$table}.bvn_verified_at",
            "{$table}.nin_verified_at",
        ];
    }

    /**
     * Mark attribute as (un)verified.
     *
     * @param \App\Enums\KycAttribute $type
     * @param bool $verified
     * @return void
     */
    public function verify(KycAttribute $type, bool $verified = true): void
    {
        $attribute = "{$type->value}_verified_at";
        $this->$attribute = $verified ? now() : null;
        $this->saveOrFail();
    }

    /**
     * Get the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}

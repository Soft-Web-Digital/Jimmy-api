<?php

namespace App\Models;

use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;
    use UUID;
    use MorphMapTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'preview_image',
        'featured_image',
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
            "{$table}.admin_id",
            "{$table}.preview_image",
            "{$table}.featured_image",
            "{$table}.created_at",
            "{$table}.updated_at",
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
     * Get the admin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * @return Attribute
     */
    public function previewImage(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? asset($value) : $value);
    }

    /**
     * @return Attribute
     */
    public function featuredImage(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? asset($value) : $value);
    }

}

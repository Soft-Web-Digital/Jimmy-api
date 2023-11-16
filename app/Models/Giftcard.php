<?php

namespace App\Models;

use App\Enums\GiftcardCardType;
use App\Enums\GiftcardStatus;
use App\Enums\GiftcardTradeType;
use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

class Giftcard extends Model implements HasMedia
{
    use HasFactory;
    use UUID;
    use MorphMapTrait;
    use ActivityLogTrait;
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'giftcard_product_id',
        'bank_id',
        'user_id',
        'parent_id',
        'group_tag',
        'account_name',
        'account_number',
        'reference',
        'status',
        'trade_type',
        'card_type',
        'code',
        'pin',
        'amount',
        'service_charge',
        'rate',
        'payable_amount',
        'comment',
        'review_note',
        'review_proof',
        'review_rate',
        'reviewed_by',
        'reviewed_at',
        'review_amount',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'code',
        'pin',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => GiftcardStatus::class,
        'trade_type' => GiftcardTradeType::class,
        'card_type' => GiftcardCardType::class,
        'code' => 'encrypted',
        'pin' => 'encrypted',
        'amount' => 'float',
        'service_charge' => 'float',
        'rate' => 'float',
        'payable_amount' => 'float',
        'review_rate' => 'float',
        'reviewed_at' => 'datetime',
        'review_amount' => 'float',
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
            "{$table}.giftcard_product_id",
            "{$table}.bank_id",
            "{$table}.user_id",
            "{$table}.parent_id",
            "{$table}.group_tag",
            "{$table}.account_name",
            "{$table}.account_number",
            "{$table}.reference",
            "{$table}.status",
            "{$table}.trade_type",
            "{$table}.card_type",
            "{$table}.code",
            "{$table}.pin",
            "{$table}.amount",
            "{$table}.service_charge",
            "{$table}.rate",
            "{$table}.comment",
            "{$table}.review_note",
            "{$table}.review_proof",
            "{$table}.review_rate",
            "{$table}.review_amount",
            "{$table}.reviewed_by",
            "{$table}.reviewed_at",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }

    public function reviewProof(): Attribute
    {
        return Attribute::make(
            fn ($value) => str_contains($value, '[') ? json_decode($value) : ($value ? [$value] : $value),
            fn ($value) => $value ? json_encode($value) : $value
        );
    }

    /**
     * Scope filter between creation dates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> ...$dates
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreationDate($query, ...$dates): \Illuminate\Database\Eloquent\Builder
    {
        $from = head($dates);
        $to = count($dates) > 1 ? $dates[1] : null;

        return $query->where(
            fn ($query) => $query->whereDate('created_at', '>=', $from)
                ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to)) // @phpstan-ignore-line
        );
    }

    /**
     * Scope filter for giftcard categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> ...$categories
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGiftcardCategories($query, ...$categories): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('giftcardProduct', fn ($query) => $query->whereIn('giftcard_category_id', $categories));
    }

    /**
     * Scope filter between payable amounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> ...$amounts
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePayableAmount($query, ...$amounts): \Illuminate\Database\Eloquent\Builder
    {
        $from = head($amounts);
        $to = count($amounts) > 1 ? $amounts[1] : null;

        return $query->where(
            fn ($query) => $query->where('payable_amount', '>=', $from)
                ->when($to, fn ($query) => $query->where('payable_amount', '<=', $to))
        );
    }

    /**
     * Send a notification to the user.
     *
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function notifyUser(Notification $notification): void
    {
        $this->user->notify($notification);
    }

    /**
     * Credit user's referee.
     *
     * @return void
     */
    public function creditReferee(): void
    {
        /** @var Referral|null $referral */
        $referral = $this->user->referee;
        if ($this->children->count() > 0) {
            $this->payable_amount += $this->children->sum('payable_amount');
        }
        if ($referral && !$referral->paid) {
            $referral->update(['cumulative_amount' => $referral->cumulative_amount + $this->payable_amount]);
        }
    }

    /**
     * Get the card media.
     *
     * @return \Illuminate\Support\HigherOrderCollectionProxy|MediaCollection|Collection

     */
    public function getCardMedia(): \Illuminate\Support\HigherOrderCollectionProxy|MediaCollection|Collection
    {
        $cards = $this->getMedia()->map->only([
            'uuid',
            'file_name',
            'mime_type',
            'original_url',
            'size',
        ]);

        $this->makeHidden(['media']);

        return $cards;
    }

    /**
     * Get the giftcard product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function giftcardProduct(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GiftcardProduct::class)->withTrashed();
    }

    /**
     * Get the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the bank.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Get the reviewer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by', 'id');
    }

    /**
     * Get the children giftcards.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }
}

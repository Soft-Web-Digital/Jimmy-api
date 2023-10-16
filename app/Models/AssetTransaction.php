<?php

namespace App\Models;

use App\Enums\AssetTransactionStatus;
use App\Enums\AssetTransactionTradeType;
use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notification;

class AssetTransaction extends Model
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
        'network_id',
        'asset_id',
        'user_id',
        'bank_id',
        'account_name',
        'account_number',
        'reference',
        'wallet_address',
        'asset_amount',
        'rate',
        'service_charge',
        'status',
        'trade_type',
        'comment',
        'payable_amount',
        'proof',
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
        //
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => AssetTransactionStatus::class,
        'trade_type' => AssetTransactionTradeType::class,
        'asset_amount' => 'float',
        'rate' => 'float',
        'service_charge' => 'float',
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
            "{$table}.network_id",
            "{$table}.asset_id",
            "{$table}.user_id",
            "{$table}.bank_id",
            "{$table}.account_name",
            "{$table}.account_number",
            "{$table}.reference",
            "{$table}.wallet_address",
            "{$table}.asset_amount",
            "{$table}.rate",
            "{$table}.service_charge",
            "{$table}.status",
            "{$table}.trade_type",
            "{$table}.comment",
            "{$table}.payable_amount",
            "{$table}.proof",
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
     * Scope filter between asset amounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> ...$amounts
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssetAmount($query, ...$amounts): \Illuminate\Database\Eloquent\Builder
    {
        $from = head($amounts);
        $to = count($amounts) > 1 ? $amounts[1] : null;

        return $query->where(
            fn ($query) => $query->where('asset_amount', '>=', $from)
                ->when($to, fn ($query) => $query->where('asset_amount', '<=', $to))
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

    public function creditReferee(): void
    {
        /** @var Referral|null $referral */
        $referral = $this->user->referee;
        if ($referral && !$referral->paid) {
            $referral->update(['cumulative_amount' => $referral->cumulative_amount + $this->payable_amount]);
        }
    }

    /**
     * Get the network.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function network(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Network::class)->withTrashed();
    }

    /**
     * Get the asset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Asset::class)->withTrashed();
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
}

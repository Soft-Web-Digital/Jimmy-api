<?php

namespace App\Models;

use App\Enums\WalletServiceType;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\NotAllowedException;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends Model
{
    use HasFactory;
    use UUID;
    use MorphMapTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_type',
        'causer_id',
        'causer_type',
        'bank_id',
        'account_name',
        'account_number',
        'status',
        'service',
        'type',
        'amount',
        'comment',
        'summary',
        'admin_note',
        'receipt',
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
        'service' => WalletServiceType::class,
        'type' => WalletTransactionType::class,
        'status' => WalletTransactionStatus::class,
        'amount' => 'float',
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
            "{$table}.user_id",
            "{$table}.user_type",
            "{$table}.causer_id",
            "{$table}.causer_type",
            "{$table}.bank_id",
            "{$table}.account_name",
            "{$table}.account_number",
            "{$table}.service",
            "{$table}.type",
            "{$table}.status",
            "{$table}.amount",
            "{$table}.comment",
            "{$table}.summary",
            "{$table}.admin_note",
            "{$table}.receipt",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }

    /**
     * Close the transaction.
     *
     * @return void
     */
    public function close(): void
    {
        throw_if(
            !in_array($this->status, [WalletTransactionStatus::PENDING]),
            NotAllowedException::class,
            "Wallet transaction's current status is {$this->status->value}."
        );

        $this->status = WalletTransactionStatus::CLOSED;
        $this->saveOrFail();
    }

    /**
     * Cancel the transaction.
     *
     * @return void
     */
    public function cancel(): void
    {
        throw_if(
            !in_array($this->status, [WalletTransactionStatus::PENDING]),
            NotAllowedException::class,
            "Wallet transaction's current status is {$this->status->value}."
        );

        $this->status = WalletTransactionStatus::CANCELLED;
        $this->summary = 'Wallet transaction can no longer be fulfilled due to insufficient balance.';
        $this->saveOrFail();
    }

    /**
     * Decline the transaction.
     *
     * @param string|null $note
     * @return void
     */
    public function decline(?string $note = null): void
    {
        throw_if(
            !in_array($this->status, [WalletTransactionStatus::PENDING]),
            NotAllowedException::class,
            "Wallet transaction's current status is {$this->status->value}."
        );

        $this->status = WalletTransactionStatus::DECLINED;
        $this->admin_note = $note;
        $this->saveOrFail();
    }

    /**
     * Scope filter between creation dates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> $dates
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreationDate($query, ...$dates): \Illuminate\Database\Eloquent\Builder
    {
        $from = head($dates);
        $to = count($dates) > 1 ? $dates[1] : null;

        return $query->where(
            fn ($query) => $query->where('created_at', '>=', $from)
                ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
        );
    }

    /**
     * Scope filter between amounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> ...$amounts
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAmount($query, ...$amounts): \Illuminate\Database\Eloquent\Builder
    {
        $from = head($amounts);
        $to = count($amounts) > 1 ? $amounts[1] : null;

        return $query->where(
            fn ($query) => $query->where('amount', '>=', $from)
                ->when($to, fn ($query) => $query->where('amount', '<=', $to))
        );
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

    /**
     * Get the causer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function causer(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
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
}

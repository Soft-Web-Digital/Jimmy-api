<?php

namespace App\Models;

use App\Contracts\Auth\HasTransactionPin;
use App\Contracts\Auth\MustSatisfyTwoFa;
use App\Contracts\Auth\MustVerifyEmail;
use App\Contracts\HasKyc;
use App\Contracts\HasWallet;
use App\Enums\KycAttribute;
use App\Traits\ActivityLogTrait;
use App\Traits\EmailVerificationCodeTrait;
use App\Traits\HasTwoFaTrait;
use App\Traits\MorphMapTrait;
use App\Traits\TransactionPinTrait;
use App\Traits\UUID;
use App\Traits\WalletTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticable implements
    MustVerifyEmail,
    MustSatisfyTwoFa,
    HasTransactionPin,
    HasWallet,
    HasKyc
{
    use HasFactory;
    use UUID;
    use MorphMapTrait;
    use ActivityLogTrait;
    use Notifiable;
    use SoftDeletes {
        restore as public restoreFromTrait;
    }
    use HasApiTokens;
    use EmailVerificationCodeTrait;
    use HasTwoFaTrait;
    use TransactionPinTrait;
    use WalletTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'country_id',
        'firstname',
        'lastname',
        'email',
        'email_verified_at',
        'username',
        'password',
        'avatar',
        'phone_number',
        'date_of_birth',
        'fcm_tokens',
        'transaction_pin_activated_at',
        'deleted_reason',
        'ref_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'transaction_pin',
        'fcm_tokens',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_pin_set' => 'boolean',
        'transaction_pin_activated_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'two_fa_activated_at' => 'datetime',
        'blocked_at' => 'datetime',
        'wallet_balance' => 'float',
        'date_of_birth' => 'date',
        'fcm_tokens' => 'array',
    ];

    /**
     * generate the referral code.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        do {
            $ref = 'KSB-' . Str::upper(Str::random(6));
        } while (static::query()->where('ref_code', $ref)->exists());

        static::creating(function ($user) use ($ref) {
            $user->ref_code = $ref;
        });
    }

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
            "{$table}.country_id",
            "{$table}.firstname",
            "{$table}.lastname",
            "{$table}.email",
            "{$table}.email_verified_at",
            "{$table}.username",
            "{$table}.avatar",
            "{$table}.phone_number",
            "{$table}.wallet_balance",
            "{$table}.ref_code",
            "{$table}.two_fa_activated_at",
            "{$table}.transaction_pin_set",
            "{$table}.transaction_pin_activated_at",
            "{$table}.blocked_at",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
            "{$table}.deleted_reason",
        ];
    }

    /**
     * Get the user's full name.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->firstname} {$this->lastname}",
        );
    }

    /**
     * Interact with the user's email.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? strtolower($value) : $value,
            set: fn ($value) => $value ? strtolower($value) : $value,
        );
    }

    /**
     * Interact with the user's username.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? strtolower($value) : $value,
            set: fn ($value) => $value ? strtolower($value) : $value,
        );
    }

    /**
     * Get the name of the log.
     *
     * @return string|null
     */
    public function getActivityLogTitle(): string|null
    {
        return $this->full_name;
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\Auth\ResetPasswordNotification($token));
    }

    /**
     * Toggle blocked_at column.
     *
     * @return void
     */
    public function toggleBlock(): void
    {
        $this->blocked_at = $this->blocked_at ? null : now();
        $this->saveOrFail();
    }

    /**
     * Delete the model from the database.
     *
     * @param string|null $reason
     * @return bool|null
     *
     * @throws \LogicException
     */
    public function delete(string|null $reason = null)
    {
        $this->updateQuietly([
            'deleted_reason' => $reason,
        ]);

        $this->tokens()->delete();

        parent::delete();
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool
     */
    public function restore(): bool
    {
        $this->deleted_reason = null;

        return $this->restoreFromTrait();
    }

    /**
     * Mark as verified
     *
     * @param \App\Enums\KycAttribute $type
     * @param bool $verified
     * @return void
     */
    public function verify(KycAttribute $type, bool $verified = true): void
    {
        /** @var \App\Models\UserKyc $kyc */
        $kyc = $this->kyc;

        $kyc->verify($type, $verified);
    }

    /**
     * Scope a query to filter results based on 'blocked_at' status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeBlocked($query, bool $active = true): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(
            $active,
            fn ($query) => $query->whereNotNull('blocked_at'),
            fn ($query) => $query->whereNull('blocked_at')
        );
    }

    /**
     * Scope a query to filter results based on 'email_verified_at' status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeEmailVerified($query, bool $active = true): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when(
            $active,
            fn ($query) => $query->whereNotNull('email_verified_at'),
            fn ($query) => $query->whereNull('email_verified_at')
        );
    }

    /**
     * Scope a query to filter based on full name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeName($query, string $name): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$name}%"]);
    }

    /**
     * Scope filter between registration dates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> ...$dates
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRegistrationDate($query, ...$dates): \Illuminate\Database\Eloquent\Builder
    {
        $from = head($dates);
        $to = count($dates) > 1 ? $dates[1] : null;

        return $query->where(
            fn ($query) => $query->whereDate('created_at', '>=', $from)
                ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to)) // @phpstan-ignore-line
        );
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
     * Get the bank accounts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bankAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserBankAccount::class);
    }

    /**
     * Get the KYC.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function kyc(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(UserKyc::class, 'user');
    }

    /**
     * Get the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function referee(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    /**
     * Get the referrals.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Referral::class, 'referee_id');
    }

    /**
     * Get the asset transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assetTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetTransaction::class);
    }

    /**
     * Get the giftcard transactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function giftcardTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Giftcard::class);
    }
}

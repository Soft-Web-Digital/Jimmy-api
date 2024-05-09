<?php

namespace App\Models;

use App\Contracts\Auth\MustSatisfyTwoFa;
use App\Contracts\Auth\MustVerifyEmail;
use App\Contracts\HasRoleContract;
use App\Traits\ActivityLogTrait;
use App\Traits\EmailVerificationCodeTrait;
use App\Traits\HasTwoFaTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements MustVerifyEmail, MustSatisfyTwoFa, HasRoleContract
{
    use HasFactory;
    use UUID;
    use MorphMapTrait;
    use ActivityLogTrait;
    use Notifiable;
    use SoftDeletes;
    use HasApiTokens;
    use EmailVerificationCodeTrait;
    use HasRoles;
    use HasTwoFaTrait;

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
        'password',
        'avatar',
        'phone_number',
        'fcm_tokens',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'fcm_tokens',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password_unprotected' => 'boolean',
        'email_verified_at' => 'datetime',
        'two_fa_activated_at' => 'datetime',
        'blocked_at' => 'datetime',
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
            "{$table}.country_id",
            "{$table}.firstname",
            "{$table}.lastname",
            "{$table}.email",
            "{$table}.email_verified_at",
            "{$table}.avatar",
            "{$table}.phone_number",
            "{$table}.two_fa_activated_at",
            "{$table}.blocked_at",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }
    /**
     * Get the admins's avatar.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => asset($value),
        );
    }

    /**
     * Get the admin's full name.
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
     * Interact with the admin's email.
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
     * Send the welcome notification.
     *
     * @param string $password
     * @param string|null $loginUrl
     * @return void
     */
    public function sendWelcomeNotification(string $password, string|null $loginUrl = null): void
    {
        $this->notify(new \App\Notifications\Admin\WelcomeNotification($password, $loginUrl));
    }

    /**
     * Send the role assigned notification.
     *
     * @param string $roleName
     * @return void
     */
    public function sendRoleAssignedNotification(string $roleName): void
    {
        $this->notify(new \App\Notifications\RoleAssignedNotification($roleName));
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
     * Get the country.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the giftcard categories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function giftcardCategories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(GiftcardCategory::class);
    }
}

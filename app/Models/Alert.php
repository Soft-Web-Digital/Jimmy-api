<?php

namespace App\Models;

use App\Enums\AlertStatus;
use App\Enums\AlertTargetUser;
use App\Enums\Queue;
use App\Jobs\AlertDispatcher;
use App\Traits\ActivityLogTrait;
use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
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
        'creator_id',
        'title',
        'body',
        'target_user',
        'dispatched_at',
        'channels',
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
        'status' => AlertStatus::class,
        'target_user' => AlertTargetUser::class,
        'dispatched_at' => 'datetime',
        'channels' => 'array',
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
            "{$table}.creator_id",
            "{$table}.title",
            "{$table}.body",
            "{$table}.status",
            "{$table}.target_user",
            "{$table}.target_user_count",
            "{$table}.dispatched_at",
            "{$table}.channels",
            "{$table}.failed_note",
            "{$table}.created_at",
            "{$table}.updated_at",
            "{$table}.deleted_at",
        ];
    }

    /**
     * Mark alert as ongoing.
     *
     * @return void
     */
    public function markAsOngoing(): void
    {
        $this->status = AlertStatus::ONGOING;
        $this->saveOrFail();

        dispatch(new AlertDispatcher($this))->onQueue(Queue::CRITICAL->value);
    }

    /**
     * Mark alert as failed.
     *
     * @param string $reason
     * @return void
     */
    public function markAsFailed(string $reason): void
    {
        $this->status = AlertStatus::FAILED;
        $this->failed_note = $reason;
        $this->saveOrFail();
    }

    /**
     * Mark alert as successful.
     *
     * @param int $recipientsCount
     * @return void
     */
    public function markAsSuccessful(int $recipientsCount): void
    {
        $this->status = AlertStatus::SUCCESSFUL;
        $this->target_user_count = $recipientsCount;
        $this->failed_note = null;
        $this->saveOrFail();
    }

    /**
     * Get the recipients.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function recipients(): \Illuminate\Support\Collection
    {
        $recipients = [];

        // Add the creator of the alert
        $recipients[] = $this->creator()->select(['id', 'firstname', 'lastname', 'email'])->get();

        /** @var \App\Enums\AlertTargetUser $targetUser */
        $targetUser = $this->target_user;

        // Add the users
        $recipients[] = User::select(['id', 'firstname', 'lastname', 'email', 'fcm_tokens'])
            ->where($targetUser->query($this->users->pluck('id')->toArray()))
            ->get();

        return collect($recipients)->collapse();
    }

    /**
     * Scope filter between dispatch dates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<int, string> ...$dates
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDispatchDate($query, ...$dates): \Illuminate\Database\Eloquent\Builder
    {
        $from = head($dates);
        $to = count($dates) > 1 ? $dates[1] : null;

        return $query->where(
            fn ($query) => $query->where('dispatched_at', '>=', $from)
                ->when($to, fn ($query) => $query->where('dispatched_at', '<=', $to))
        );
    }

    /**
     * Get the creator.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'creator_id')->withTrashed();
    }

    /**
     * Get the users to alert.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alert_user');
    }
}

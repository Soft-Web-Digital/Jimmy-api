<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    /**
     * Mark notifications as read.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array<int, string>|string $notificationIds
     * @return void
     */
    public function markAsRead(Model $model, array|string $notificationIds): void
    {
        DatabaseNotification::query()
            ->whereMorphedTo('notifiable', $model)
            ->whereNull('read_at')
            ->whereIn('id', is_string($notificationIds) ? [$notificationIds] : $notificationIds)
            ->update([
                'read_at' => now(),
            ]);
    }
}

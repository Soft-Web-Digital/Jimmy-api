<?php

declare(strict_types=1);

namespace App\Events\Admin;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class AdminNotified
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Notifications\Notification $notification
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @return void
     */
    public function __construct(public Notification $notification, public ?Model $model = null)
    {
        //
    }
}

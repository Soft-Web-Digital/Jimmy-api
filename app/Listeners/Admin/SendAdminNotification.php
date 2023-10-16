<?php

namespace App\Listeners\Admin;

use App\Enums\Permission;
use App\Events\Admin\AdminNotified;
use App\Models\Admin;
use App\Models\Giftcard;
use Illuminate\Support\Facades\Notification;

class SendAdminNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\Admin\AdminNotified $event
     * @return void
     */
    public function handle(AdminNotified $event)
    {
        $admins = Admin::permission(Permission::RECEIVE_NOTIFICATIONS->value)->get();

        if ($event->model && get_class($event->model) == Giftcard::class) {
            $newAdmins = $event->model->giftcardProduct->giftcardCategory->admins()->get();
            $admins = collect($admins)->merge($newAdmins)->unique();
        }

        Notification::send($admins, $event->notification);
    }
}

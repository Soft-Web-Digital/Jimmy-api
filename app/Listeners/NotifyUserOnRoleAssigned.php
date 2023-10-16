<?php

namespace App\Listeners;

use App\Events\RoleAssigned;

class NotifyUserOnRoleAssigned
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
     * @param \App\Events\RoleAssigned $event
     * @return void
     */
    public function handle(RoleAssigned $event)
    {
        $event->user->sendRoleAssignedNotification($event->roleName);
    }
}

<?php

namespace App\Listeners\Admin;

use App\Events\Admin\Registered;

class SendWelcomeMail
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
     * @param \App\Events\Admin\Registered $event
     * @return void
     */
    public function handle(Registered $event)
    {
        $event->admin->sendWelcomeNotification($event->password, $event->loginUrl);
    }
}

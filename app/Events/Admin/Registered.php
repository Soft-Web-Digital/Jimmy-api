<?php

declare(strict_types=1);

namespace App\Events\Admin;

use App\Models\Admin;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Registered
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Admin $admin
     * @param string $password
     * @param string|null $loginUrl
     * @return void
     */
    public function __construct(public Admin $admin, public string $password, public string|null $loginUrl = null)
    {
        //
    }
}

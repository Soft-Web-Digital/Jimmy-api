<?php

declare(strict_types=1);

namespace App\Events;

use App\Contracts\HasRoleContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleAssigned
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param \App\Contracts\HasRoleContract $user
     * @param string $roleName
     * @return void
     */
    public function __construct(public HasRoleContract $user, public string $roleName)
    {
        //
    }
}

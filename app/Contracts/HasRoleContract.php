<?php

declare(strict_types=1);

namespace App\Contracts;

interface HasRoleContract
{
    /**
     * Send the role assigned notification.
     *
     * @param string $roleName
     * @return void
     */
    public function sendRoleAssignedNotification(string $roleName): void;
}

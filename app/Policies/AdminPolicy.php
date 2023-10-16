<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\Admin $admin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(Admin $admin): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\Admin $admin
     * @param \App\Models\Admin $otherAdmin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(Admin $admin, Admin $otherAdmin): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\Models\Admin $admin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(Admin $admin): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\Admin $admin
     * @param \App\Models\Admin $otherAdmin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(Admin $admin, Admin $otherAdmin): \Illuminate\Auth\Access\Response|bool
    {
        return $otherAdmin->hasRole('SUPERADMIN')
            ? Response::deny('You are not authorized to manage superadmins')
            : Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\Admin $admin
     * @param \App\Models\Admin $otherAdmin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(Admin $admin, Admin $otherAdmin): \Illuminate\Auth\Access\Response|bool
    {
        return $otherAdmin->hasRole('SUPERADMIN')
            ? Response::deny('You are not authorized to manage superadmins')
            : Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param \App\Models\Admin $admin
     * @param \App\Models\Admin $otherAdmin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(Admin $admin, Admin $otherAdmin): \Illuminate\Auth\Access\Response|bool
    {
        return $otherAdmin->hasRole('SUPERADMIN')
            ? Response::deny('You are not authorized to manage superadmins')
            : Response::allow();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param \App\Models\Admin $admin
     * @param \App\Models\Admin $otherAdmin
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(Admin $admin, Admin $otherAdmin): \Illuminate\Auth\Access\Response|bool
    {
        return $otherAdmin->hasRole('SUPERADMIN')
            ? Response::deny('You are not authorized to manage superadmins')
            : Response::allow();
    }
}

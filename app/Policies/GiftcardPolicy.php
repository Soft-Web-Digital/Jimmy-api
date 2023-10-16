<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Giftcard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GiftcardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Giftcard $giftcard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Giftcard $giftcard): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Giftcard $giftcard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Giftcard $giftcard): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Giftcard $giftcard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Giftcard $giftcard): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Giftcard $giftcard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Giftcard $giftcard): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Giftcard $giftcard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Giftcard $giftcard): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the admin can manage (decline or approve) the sale.
     *
     * @param \App\Models\Admin $admin
     * @param \App\Models\Giftcard $giftcard
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manage(Admin $admin, Giftcard $giftcard): \Illuminate\Auth\Access\Response|bool
    {
        return $admin->hasRole('SUPERADMIN') || $admin->giftcardCategories()
            ->where('id', $giftcard->giftcardProduct()->value('giftcard_category_id'))
            ->exists();
    }
}

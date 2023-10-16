<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserBankAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserBankAccountPolicy
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
     * @param \App\Models\UserBankAccount $userBankAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, UserBankAccount $userBankAccount): \Illuminate\Auth\Access\Response|bool
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
     * @param \App\Models\UserBankAccount $userBankAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, UserBankAccount $userBankAccount): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\UserBankAccount $userBankAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, UserBankAccount $userBankAccount): \Illuminate\Auth\Access\Response|bool
    {
        return $user->id === $userBankAccount->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\UserBankAccount $userBankAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, UserBankAccount $userBankAccount): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\UserBankAccount $userBankAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, UserBankAccount $userBankAccount): \Illuminate\Auth\Access\Response|bool
    {
        return false;
    }
}

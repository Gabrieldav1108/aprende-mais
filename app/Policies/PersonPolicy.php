<?php

namespace App\Policies;

use App\Models\User;

class PersonPolicy
{
    /**
     * Determine whether the user can list people and their access accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    /**
     * Determine whether the user can register people together with their access account.
     */
    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    /**
     * Determine whether the user can update people and their access account.
     */
    public function update(User $user): bool
    {
        return $user->can('users.manage');
    }
}

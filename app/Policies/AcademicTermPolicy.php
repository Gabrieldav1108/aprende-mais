<?php

namespace App\Policies;

use App\Models\User;

class AcademicTermPolicy
{
    /**
     * Determine whether the user can create academic terms.
     */
    public function create(User $user): bool
    {
        return $user->can('terms.create');
    }

    /**
     * Determine whether the user can update academic terms.
     */
    public function update(User $user): bool
    {
        return $user->can('terms.update');
    }
}

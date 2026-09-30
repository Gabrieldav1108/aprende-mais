<?php

namespace App\Policies;

use App\Models\User;

class SubjectPolicy
{
    /**
     * Determine whether the user can create subjects.
     */
    public function create(User $user): bool
    {
        return $user->can('subjects.create');
    }

    /**
     * Determine whether the user can delete subjects.
     */
    public function delete(User $user): bool
    {
        return $user->can('subjects.delete');
    }
}

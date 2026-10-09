<?php

namespace App\Policies;

use App\Models\User;

class SubjectPolicy
{
    /**
     * Determine whether the user can list subjects.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('subjects.view');
    }

    /**
     * Determine whether the user can update subjects.
     */
    public function update(User $user): bool
    {
        return $user->can('subjects.update');
    }

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

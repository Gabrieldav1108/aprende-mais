<?php

namespace App\Policies;

use App\Models\User;

class ClassroomSubjectPolicy
{
    /**
     * Determine whether the user can add subjects to a classroom.
     */
    public function create(User $user): bool
    {
        return $user->can('subjects.create');
    }

    /**
     * Determine whether the user can remove subjects from a classroom.
     */
    public function delete(User $user): bool
    {
        return $user->can('subjects.delete');
    }
}

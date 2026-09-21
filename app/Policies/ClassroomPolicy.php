<?php

namespace App\Policies;

use App\Models\User;

class ClassroomPolicy
{
    /**
     * Determine whether the user can create classrooms.
     */
    public function create(User $user): bool
    {
        return $user->can('classrooms.create');
    }

    /**
     * Determine whether the user can update classrooms.
     */
    public function update(User $user): bool
    {
        return $user->can('classrooms.update');
    }

    /**
     * Determine whether the user can delete classrooms.
     */
    public function delete(User $user): bool
    {
        return $user->can('classrooms.delete');
    }
}

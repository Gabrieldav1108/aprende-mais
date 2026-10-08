<?php

namespace App\Policies;

use App\Models\User;

class TeacherPolicy
{
    /**
     * Determine whether the user can list teachers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('teachers.view');
    }

    /**
     * Determine whether the user can register teachers.
     */
    public function create(User $user): bool
    {
        return $user->can('teachers.create');
    }

    /**
     * Determine whether the user can update teachers.
     */
    public function update(User $user): bool
    {
        return $user->can('teachers.update');
    }
}

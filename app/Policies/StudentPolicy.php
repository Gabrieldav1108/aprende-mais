<?php

namespace App\Policies;

use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can list and view students.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('students.view');
    }

    /**
     * Determine whether the user can view a student.
     */
    public function view(User $user): bool
    {
        return $user->can('students.view');
    }

    /**
     * Determine whether the user can register students.
     */
    public function create(User $user): bool
    {
        return $user->can('students.create');
    }

    /**
     * Determine whether the user can update students.
     */
    public function update(User $user): bool
    {
        return $user->can('students.update');
    }
}

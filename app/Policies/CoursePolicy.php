<?php

namespace App\Policies;

use App\Models\User;

class CoursePolicy
{
    /**
     * Determine whether the user can list courses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('courses.view');
    }

    /**
     * Determine whether the user can view a course.
     */
    public function view(User $user): bool
    {
        return $user->can('courses.view');
    }

    /**
     * Determine whether the user can create courses.
     */
    public function create(User $user): bool
    {
        return $user->can('courses.create');
    }

    /**
     * Determine whether the user can update courses.
     */
    public function update(User $user): bool
    {
        return $user->can('courses.update');
    }

    /**
     * Determine whether the user can delete courses.
     */
    public function delete(User $user): bool
    {
        return $user->can('courses.delete');
    }
}

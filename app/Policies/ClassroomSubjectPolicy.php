<?php

namespace App\Policies;

use App\Models\ClassroomSubject;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ClassroomSubjectPolicy
{
    /**
     * Determine whether the user can view a classroom subject, which follows the access to its classroom.
     */
    public function view(User $user, ClassroomSubject $classroomSubject): Response
    {
        return Gate::forUser($user)->inspect('view', $classroomSubject->classroom);
    }

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

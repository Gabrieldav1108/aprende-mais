<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClassroomPolicy
{
    /**
     * Determine whether the user can list classrooms.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('classrooms.view');
    }

    /**
     * Determine whether the user can view a classroom, answering not found for a classroom they do not teach.
     */
    public function view(User $user, Classroom $classroom): Response
    {
        if (! $user->can('classrooms.view')) {
            return Response::deny();
        }

        return Classroom::query()->visibleTo($user)->whereKey($classroom->id)->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

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

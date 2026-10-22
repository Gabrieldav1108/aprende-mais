<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Models\ClassroomSubject;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ClassroomSubjectPolicy
{
    /**
     * Determine whether the user can view a classroom subject, which follows the access to its classroom,
     * or, for a student, an active enrollment in the classroom.
     */
    public function view(User $user, ClassroomSubject $classroomSubject): Response
    {
        $classroomAccess = Gate::forUser($user)->inspect('view', $classroomSubject->classroom);

        if ($classroomAccess->allowed() || ! $user->can('subjects.view-enrolled')) {
            return $classroomAccess;
        }

        return $this->isEnrolledIn($user, $classroomSubject)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can list the classroom subjects they are enrolled in.
     */
    public function viewEnrolled(User $user): bool
    {
        return $user->can('subjects.view-enrolled');
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

    /**
     * Whether the user is a student with an active enrollment in the classroom subject's classroom.
     */
    private function isEnrolledIn(User $user, ClassroomSubject $classroomSubject): bool
    {
        $student = $user->student;

        return $student !== null && $student->classroomStudents()
            ->where('classroom_id', $classroomSubject->classroom_id)
            ->where('status', EnrollmentStatus::Active)
            ->exists();
    }
}

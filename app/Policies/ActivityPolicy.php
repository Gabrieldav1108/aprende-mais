<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\ClassroomSubject;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Whoever sees the classroom subject sees its activities; only its teacher manages them. Admins pass through Gate::before.
 */
class ActivityPolicy
{
    /**
     * Determine whether the user can view the activity and download its files, which follows the access to its classroom subject.
     */
    public function view(User $user, Activity $activity): Response
    {
        return Gate::forUser($user)->inspect('view', $activity->classroomSubject);
    }

    /**
     * Determine whether the user can post activities to the classroom subject.
     */
    public function create(User $user, ClassroomSubject $classroomSubject): bool
    {
        return $this->teaches($user, $classroomSubject);
    }

    /**
     * Determine whether the user can edit the activity.
     */
    public function update(User $user, Activity $activity): bool
    {
        return $this->teaches($user, $activity->classroomSubject);
    }

    /**
     * Determine whether the user can delete the activity.
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $this->teaches($user, $activity->classroomSubject);
    }

    /**
     * Whether the user is the teacher of the classroom subject.
     */
    private function teaches(User $user, ClassroomSubject $classroomSubject): bool
    {
        $teacher = $user->teacher;

        return $teacher !== null && $teacher->id === $classroomSubject->teacher_id;
    }
}

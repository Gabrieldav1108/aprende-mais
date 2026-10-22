<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\ClassroomSubject;
use App\Models\User;

/**
 * Only the teacher of the classroom subject manages its announcements; admins pass through Gate::before.
 */
class AnnouncementPolicy
{
    /**
     * Determine whether the user can post announcements to the classroom subject.
     */
    public function create(User $user, ClassroomSubject $classroomSubject): bool
    {
        return $this->teaches($user, $classroomSubject);
    }

    /**
     * Determine whether the user can edit the announcement.
     */
    public function update(User $user, Announcement $announcement): bool
    {
        return $this->teaches($user, $announcement->classroomSubject);
    }

    /**
     * Determine whether the user can delete the announcement.
     */
    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->teaches($user, $announcement->classroomSubject);
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

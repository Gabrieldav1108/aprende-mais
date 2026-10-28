<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A student actively enrolled in the classroom answers the activity and sees their own answers; the teacher of the
 * classroom subject sees every one of them. Admins pass through Gate::before.
 */
class SubmissionPolicy
{
    /**
     * Determine whether the user can send a new version of their answer to the activity. The first answer is accepted
     * even after the due date, as a late one; once sent, it can only be changed until the due date.
     */
    public function create(User $user, Activity $activity): Response
    {
        $student = $user->student;

        if ($student === null || ! $student->isActivelyEnrolledIn($activity->classroomSubject->classroom_id)) {
            return Response::deny();
        }

        if (! $activity->isOpen() && $activity->submissions()->where('student_id', $student->id)->exists()) {
            return Response::deny(__('O prazo da atividade terminou e a entrega não pode mais ser alterada.'));
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the submission and download its files, while its activity is not deleted.
     */
    public function view(User $user, Submission $submission): bool
    {
        $activity = $submission->activity;

        if ($activity === null) {
            return false;
        }

        return $user->student?->id === $submission->student_id || $user->can('viewSubmissions', $activity);
    }
}

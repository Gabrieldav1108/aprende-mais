<?php

namespace App\Repositories;

use App\Enums\EnrollmentStatus;
use App\Exceptions\RepositoryException;
use App\Models\Activity;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubmissionRepository
{
    public function __construct(private readonly AttachmentRepository $attachments) {}

    /**
     * Store a new version of the student's answer to the activity, keeping the earlier ones. The files that come from
     * the current version are copied into the new one, so every version keeps the files it was sent with.
     *
     * @param  array{content?: string|null, attachment_ids?: list<int>}  $data
     */
    public function submit(Activity $activity, Student $student, array $data): Submission
    {
        try {
            return DB::transaction(function () use ($activity, $student, $data): Submission {
                $attachmentIds = $data['attachment_ids'] ?? [];
                $current = $activity->submissions()->where('student_id', $student->id)->latest('id')->first();
                $kept = $current?->attachments()->whereKey($attachmentIds)->get() ?? new Collection;

                $submission = $activity->submissions()->create([
                    'student_id' => $student->id,
                    'content' => $data['content'] ?? null,
                ]);

                $this->attachments->syncTo($submission, array_values(array_diff($attachmentIds, $kept->modelKeys())));
                $this->attachments->copyTo($submission, $kept);

                return $submission;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to submit activity.', ['activity_id' => $activity->id, 'student_id' => $student->id], $e);
        }
    }

    /**
     * Get the students who answer the activity, ordered by name, each with every version they sent, newest first: the
     * ones actively enrolled in its classroom and anyone else who already sent an answer.
     *
     * @return Collection<int, Student>
     */
    public function forActivity(Activity $activity): Collection
    {
        try {
            return Student::query()
                ->where(fn (Builder $query) => $query
                    ->whereHas('classroomStudents', fn (Builder $query) => $query
                        ->where('classroom_id', $activity->classroomSubject->classroom_id)
                        ->where('status', EnrollmentStatus::Active))
                    ->orWhereHas('submissions', fn (Builder $query) => $query->where('activity_id', $activity->id)))
                ->with([
                    'person:id,name',
                    'submissions' => fn (HasMany $query) => $query
                        ->where('activity_id', $activity->id)
                        ->with(['attachments' => fn (MorphMany $query) => $query
                            ->select(['id', 'attachable_type', 'attachable_id', 'original_name', 'mime_type', 'size'])
                            ->orderBy('id')])
                        ->latest('id'),
                ])
                ->get(['id', 'person_id', 'enrollment_number'])
                ->sortBy('person.name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load submissions.', ['activity_id' => $activity->id], $e);
        }
    }
}

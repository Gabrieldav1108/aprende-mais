<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Activity;
use App\Models\ClassroomSubject;
use App\Models\User;
use App\Notifications\ActivityPublishedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ActivityRepository
{
    public function __construct(
        private readonly ClassroomSubjectRepository $classroomSubjects,
        private readonly AttachmentRepository $attachments,
    ) {}

    /**
     * Get the classroom subject's activities, newest first, with their author and supporting files.
     *
     * @return Collection<int, Activity>
     */
    public function forClassroomSubject(ClassroomSubject $classroomSubject): Collection
    {
        try {
            return $classroomSubject->activities()
                ->with([
                    'author:id,name',
                    'attachments' => fn (MorphMany $query) => $query
                        ->select(['id', 'attachable_type', 'attachable_id', 'original_name', 'mime_type', 'size'])
                        ->orderBy('id'),
                ])
                ->latest()
                ->latest('id')
                ->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load activities.', ['classroom_subject_id' => $classroomSubject->id], $e);
        }
    }

    /**
     * Post an activity with its supporting files to the classroom subject and notify the students actively enrolled in its classroom.
     *
     * @param  array{title: string, description: string, due_at?: string|null, attachment_ids?: list<int>, send_email?: bool}  $data
     */
    public function publish(ClassroomSubject $classroomSubject, User $author, array $data): Activity
    {
        try {
            return DB::transaction(function () use ($classroomSubject, $author, $data): Activity {
                $activity = $classroomSubject->activities()->create([
                    'author_id' => $author->id,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'due_at' => $data['due_at'] ?? null,
                    'sent_by_email' => (bool) ($data['send_email'] ?? false),
                ]);

                $this->attachments->syncTo($activity, $data['attachment_ids'] ?? []);

                $activity->setRelation('classroomSubject', $classroomSubject->loadMissing(['subject', 'classroom']));

                Notification::send($this->classroomSubjects->activeStudentUsers($classroomSubject), new ActivityPublishedNotification($activity));

                return $activity;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to publish activity.', ['classroom_subject_id' => $classroomSubject->id], $e);
        }
    }

    /**
     * Change the activity's title, description, due date and supporting files, without notifying the students again.
     *
     * @param  array{title: string, description: string, due_at?: string|null, attachment_ids?: list<int>}  $data
     */
    public function update(Activity $activity, array $data): Activity
    {
        try {
            return DB::transaction(function () use ($activity, $data): Activity {
                $activity->update([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'due_at' => $data['due_at'] ?? null,
                ]);

                $this->attachments->syncTo($activity, $data['attachment_ids'] ?? []);

                return $activity;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update activity.', ['activity_id' => $activity->id], $e);
        }
    }

    /**
     * Soft delete the activity; its files stay in the bucket so it can be restored, but can no longer be downloaded.
     */
    public function delete(Activity $activity): void
    {
        try {
            $activity->delete();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to delete activity.', ['activity_id' => $activity->id], $e);
        }
    }
}

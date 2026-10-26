<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Announcement;
use App\Models\ClassroomSubject;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AnnouncementRepository
{
    public function __construct(private readonly ClassroomSubjectRepository $classroomSubjects) {}

    /**
     * Get the classroom subject's announcements, pinned ones first and then newest first, with their author.
     *
     * @return Collection<int, Announcement>
     */
    public function forClassroomSubject(ClassroomSubject $classroomSubject): Collection
    {
        try {
            return $classroomSubject->announcements()
                ->with('author:id,name')
                ->orderByDesc('is_pinned')
                ->latest()
                ->latest('id')
                ->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load announcements.', ['classroom_subject_id' => $classroomSubject->id], $e);
        }
    }

    /**
     * Post an announcement to the classroom subject and notify the students actively enrolled in its classroom.
     *
     * @param  array{title: string, body: string, is_pinned?: bool, send_email?: bool}  $data
     */
    public function publish(ClassroomSubject $classroomSubject, User $author, array $data): Announcement
    {
        try {
            return DB::transaction(function () use ($classroomSubject, $author, $data): Announcement {
                $announcement = $classroomSubject->announcements()->create([
                    'author_id' => $author->id,
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'is_pinned' => (bool) ($data['is_pinned'] ?? false),
                    'sent_by_email' => (bool) ($data['send_email'] ?? false),
                ]);

                $announcement->setRelation('classroomSubject', $classroomSubject->loadMissing(['subject', 'classroom']));

                Notification::send($this->classroomSubjects->activeStudentUsers($classroomSubject), new AnnouncementPublishedNotification($announcement));

                return $announcement;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to publish announcement.', ['classroom_subject_id' => $classroomSubject->id], $e);
        }
    }

    /**
     * Change the announcement's title, body and pin, without notifying the students again.
     *
     * @param  array{title: string, body: string, is_pinned?: bool}  $data
     */
    public function update(Announcement $announcement, array $data): Announcement
    {
        try {
            $announcement->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            ]);

            return $announcement;
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update announcement.', ['announcement_id' => $announcement->id], $e);
        }
    }

    /**
     * Remove the announcement.
     */
    public function delete(Announcement $announcement): void
    {
        try {
            $announcement->delete();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to delete announcement.', ['announcement_id' => $announcement->id], $e);
        }
    }
}

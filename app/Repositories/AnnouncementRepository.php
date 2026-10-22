<?php

namespace App\Repositories;

use App\Enums\EnrollmentStatus;
use App\Exceptions\RepositoryException;
use App\Models\Announcement;
use App\Models\ClassroomSubject;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AnnouncementRepository
{
    /**
     * Get the classroom subject's announcements, newest first, with their author.
     *
     * @return Collection<int, Announcement>
     */
    public function forClassroomSubject(ClassroomSubject $classroomSubject): Collection
    {
        try {
            return $classroomSubject->announcements()
                ->with('author:id,name')
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
     * @param  array{title: string, body: string, send_email?: bool}  $data
     */
    public function publish(ClassroomSubject $classroomSubject, User $author, array $data): Announcement
    {
        try {
            return DB::transaction(function () use ($classroomSubject, $author, $data): Announcement {
                $announcement = $classroomSubject->announcements()->create([
                    'author_id' => $author->id,
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'sent_by_email' => (bool) ($data['send_email'] ?? false),
                ]);

                $announcement->setRelation('classroomSubject', $classroomSubject->loadMissing(['subject', 'classroom']));

                Notification::send($this->recipientsFor($classroomSubject), new AnnouncementPublishedNotification($announcement));

                return $announcement;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to publish announcement.', ['classroom_subject_id' => $classroomSubject->id], $e);
        }
    }

    /**
     * Get the users of the students actively enrolled in the classroom subject's classroom.
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(ClassroomSubject $classroomSubject): Collection
    {
        return User::query()
            ->whereHas('student.classroomStudents', fn (Builder $query) => $query
                ->where('classroom_id', $classroomSubject->classroom_id)
                ->where('status', EnrollmentStatus::Active))
            ->get();
    }

    /**
     * Change the announcement's title and body, without notifying the students again.
     *
     * @param  array{title: string, body: string}  $data
     */
    public function update(Announcement $announcement, array $data): Announcement
    {
        try {
            $announcement->update([
                'title' => $data['title'],
                'body' => $data['body'],
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

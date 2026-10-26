<?php

namespace App\Repositories;

use App\Enums\EnrollmentStatus;
use App\Exceptions\RepositoryException;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ClassroomSubjectRepository
{
    /**
     * Offer an existing subject of the classroom's course, taught by the subject's teacher.
     */
    public function attachToClassroom(Classroom $classroom, int $subjectId): ClassroomSubject
    {
        try {
            $subject = Subject::query()->findOrFail($subjectId);

            if (! $classroom->course->subjects()->whereKey($subject->id)->exists()) {
                throw new RepositoryException('Subject does not belong to the classroom course.', [
                    'classroom_id' => $classroom->id,
                    'subject_id' => $subjectId,
                ]);
            }

            if ($subject->teacher_id === null) {
                throw new RepositoryException('Subject has no teacher.', [
                    'classroom_id' => $classroom->id,
                    'subject_id' => $subjectId,
                ]);
            }

            return $classroom->classroomSubjects()->create([
                'subject_id' => $subject->id,
                'teacher_id' => $subject->teacher_id,
            ]);
        } catch (RepositoryException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RepositoryException(
                'Failed to add subject to classroom.',
                ['classroom_id' => $classroom->id, 'subject_id' => $subjectId],
                $e,
            );
        }
    }

    /**
     * Load the classroom subject's classroom, subject and teacher (only the name of their person, as students see it too) for display.
     */
    public function withDetails(ClassroomSubject $classroomSubject): ClassroomSubject
    {
        try {
            return $classroomSubject->load(['classroom.course', 'classroom.academicTerm', 'subject', 'teacher.person:id,name']);
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load classroom subject.', ['classroom_subject_id' => $classroomSubject->id], $e);
        }
    }

    /**
     * Get the students enrolled in the classroom subject's classroom, ordered by name.
     *
     * @return Collection<int, ClassroomStudent>
     */
    public function studentsFor(ClassroomSubject $classroomSubject): Collection
    {
        try {
            return $classroomSubject->classroom->classroomStudents()
                ->with('student.person')
                ->get()
                ->sortBy('student.person.name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load classroom subject students.', ['classroom_subject_id' => $classroomSubject->id], $e);
        }
    }

    /**
     * Get the users of the students actively enrolled in the classroom subject's classroom, who are notified of what is posted to it.
     *
     * @return Collection<int, User>
     */
    public function activeStudentUsers(ClassroomSubject $classroomSubject): Collection
    {
        return User::query()
            ->whereHas('student.classroomStudents', fn (Builder $query) => $query
                ->where('classroom_id', $classroomSubject->classroom_id)
                ->where('status', EnrollmentStatus::Active))
            ->get();
    }

    /**
     * Get the subjects of the classrooms where the user's student is actively enrolled, ordered by subject name.
     *
     * @return Collection<int, ClassroomSubject>
     */
    public function enrolledBy(User $user): Collection
    {
        $student = $user->student;

        if ($student === null) {
            return new Collection;
        }

        try {
            return ClassroomSubject::query()
                ->whereHas('classroom.classroomStudents', fn (Builder $query) => $query
                    ->where('student_id', $student->id)
                    ->where('status', EnrollmentStatus::Active))
                ->with(['classroom.academicTerm', 'subject', 'teacher.person:id,name'])
                ->get()
                ->sortBy('subject.name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load enrolled subjects.', ['user_id' => $user->id], $e);
        }
    }

    /**
     * Remove the subject from the classroom.
     */
    public function delete(ClassroomSubject $classroomSubject): void
    {
        try {
            $classroomSubject->delete();
        } catch (Throwable $e) {
            throw new RepositoryException(
                'Failed to remove subject from classroom.',
                ['classroom_subject_id' => $classroomSubject->id],
                $e,
            );
        }
    }
}

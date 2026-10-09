<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Subject;
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

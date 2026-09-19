<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Throwable;

class ClassroomSubjectRepository
{
    /**
     * Create a subject and attach it to the classroom with a teacher.
     *
     * @param  array<string, mixed>  $subjectData
     */
    public function createForClassroom(Classroom $classroom, array $subjectData, int $teacherId): ClassroomSubject
    {
        try {
            return DB::transaction(function () use ($classroom, $subjectData, $teacherId) {
                $subject = Subject::create($subjectData);

                return $classroom->classroomSubjects()->create([
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacherId,
                ]);
            });
        } catch (Throwable $e) {
            throw new RepositoryException(
                'Failed to add subject to classroom.',
                ['classroom_id' => $classroom->id, 'subject_data' => $subjectData, 'teacher_id' => $teacherId],
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

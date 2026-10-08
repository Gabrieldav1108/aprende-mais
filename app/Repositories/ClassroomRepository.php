<?php

namespace App\Repositories;

use App\Enums\EnrollmentStatus;
use App\Exceptions\RepositoryException;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ClassroomRepository
{
    /**
     * List all classrooms with their course, term, homeroom teacher and counts.
     *
     * @return Collection<int, Classroom>
     */
    public function all(): Collection
    {
        try {
            return Classroom::query()
                ->with(['course', 'academicTerm', 'homeroomTeacher.person'])
                ->withCount(['classroomSubjects', 'classroomStudents'])
                ->latest()
                ->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list classrooms.', previous: $e);
        }
    }

    /**
     * Create a new classroom in the school that owns the given course.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Classroom
    {
        try {
            $course = Course::query()->findOrFail($data['course_id']);

            return Classroom::query()->create([...$data, 'school_id' => $course->school_id]);
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create classroom.', ['data' => $data], $e);
        }
    }

    /**
     * Load the classroom's relations for display.
     */
    public function withDetails(Classroom $classroom): Classroom
    {
        try {
            return $classroom
                ->load(['course', 'academicTerm', 'homeroomTeacher.person'])
                ->loadCount('classroomStudents');
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load classroom.', ['classroom_id' => $classroom->id], $e);
        }
    }

    /**
     * Get the classroom's subjects with their teacher.
     *
     * @return Collection<int, ClassroomSubject>
     */
    public function subjectsFor(Classroom $classroom): Collection
    {
        try {
            return $classroom->classroomSubjects()->with(['subject', 'teacher.person'])->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load classroom subjects.', ['classroom_id' => $classroom->id], $e);
        }
    }

    /**
     * Update an existing classroom.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Classroom $classroom, array $data): Classroom
    {
        try {
            $classroom->update($data);

            return $classroom;
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update classroom.', ['classroom_id' => $classroom->id, 'data' => $data], $e);
        }
    }

    /**
     * Enroll a student in the classroom, respecting its capacity.
     */
    public function enrollStudent(Classroom $classroom, int $studentId): ClassroomStudent
    {
        try {
            return DB::transaction(function () use ($classroom, $studentId) {
                $locked = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);

                if ($locked->classroomStudents()->count() >= $locked->capacity) {
                    throw new RepositoryException('Classroom is full.', ['classroom_id' => $classroom->id]);
                }

                return $locked->classroomStudents()->create([
                    'student_id' => $studentId,
                    'enrolled_at' => now(),
                    'status' => EnrollmentStatus::Active,
                ]);
            });
        } catch (RepositoryException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to enroll student.', ['classroom_id' => $classroom->id, 'student_id' => $studentId], $e);
        }
    }
}

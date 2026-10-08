<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Person;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class StudentRepository
{
    /**
     * List the students of the school with their person, login e-mail and course.
     *
     * @return Collection<int, Student>
     */
    public function all(): Collection
    {
        try {
            return Student::query()
                ->with(['person.user:id,email', 'course:id,name'])
                ->get()
                ->sortBy('person.name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list students.', previous: $e);
        }
    }

    /**
     * Make an existing person with the student role a student.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Student
    {
        try {
            return DB::transaction(function () use ($data): Student {
                $person = Person::query()->findOrFail($data['person_id']);

                return Student::query()->create([
                    'person_id' => $person->id,
                    'course_id' => $data['course_id'] ?? null,
                    'enrollment_number' => $data['enrollment_number'],
                    'status' => $data['status'],
                ]);
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create student.', ['person_id' => $data['person_id'] ?? null], $e);
        }
    }

    /**
     * Update the student data.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        try {
            $student->update([
                'course_id' => $data['course_id'] ?? null,
                'enrollment_number' => $data['enrollment_number'],
                'status' => $data['status'],
            ]);

            return $student;
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update student.', ['student_id' => $student->id], $e);
        }
    }
}

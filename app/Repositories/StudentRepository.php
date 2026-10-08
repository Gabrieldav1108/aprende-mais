<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class StudentRepository
{
    public function __construct(private readonly PersonRepository $people) {}

    /**
     * List the students of the school with their person data, contacts, login e-mail and course.
     *
     * @return Collection<int, Student>
     */
    public function all(): Collection
    {
        try {
            return Student::query()
                ->with(['person.user:id,email', 'person.address', 'person.phones', 'person.emails', 'course:id,name'])
                ->get()
                ->sortBy('person.name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list students.', previous: $e);
        }
    }

    /**
     * Register a student together with their user account and person data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Student
    {
        try {
            return DB::transaction(function () use ($data): Student {
                $person = $this->people->createWithUser($data, 'student');

                return Student::query()->create([
                    'person_id' => $person->id,
                    'course_id' => $data['course_id'] ?? null,
                    'enrollment_number' => $data['enrollment_number'],
                    'status' => $data['status'],
                ]);
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create student.', ['email' => $data['email'] ?? null], $e);
        }
    }

    /**
     * Update a student together with their user account and person data.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        try {
            return DB::transaction(function () use ($student, $data): Student {
                $this->people->update($student->person, $data);

                $student->update([
                    'course_id' => $data['course_id'] ?? null,
                    'enrollment_number' => $data['enrollment_number'],
                    'status' => $data['status'],
                ]);

                return $student;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update student.', ['student_id' => $student->id], $e);
        }
    }
}

<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Course;
use App\Models\Person;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class StudentRepository
{
    /**
     * List the students of the school the user may see with their person, login e-mail and course.
     *
     * @return Collection<int, Student>
     */
    public function all(User $user): Collection
    {
        try {
            return Student::query()
                ->visibleTo($user)
                ->with(['person.user:id,email', 'course:id,name'])
                ->get()
                ->sortBy('person.name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list students.', previous: $e);
        }
    }

    /**
     * Paginate the students the user may see, applying the given filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Student::query()
                ->visibleTo($user)
                ->with(['person.user:id,email', 'course:id,name']);

            $this->applyFilters($query, $filters);
            $this->applySorting($query, $filters);

            return $query->paginate((int) ($filters['per_page'] ?? 15));
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to paginate students.', previous: $e);
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

    /**
     * Apply the search, course and status filters.
     *
     * @param  Builder<Student>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function (Builder $query, mixed $search): void {
                // Grouped, so the ORs cannot escape the visibleTo() scope.
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('enrollment_number', 'like', "%{$search}%")
                        ->orWhereHas('person', function (Builder $person) use ($search): void {
                            $person
                                ->where('name', 'like', "%{$search}%")
                                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($filters['course_id'] ?? null, fn (Builder $query, mixed $courseId) => $query->where('course_id', $courseId))
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $status) => $query->where('status', $status));
    }

    /**
     * Sort in SQL (not in PHP) so the order is consistent across pages.
     *
     * @param  Builder<Student>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $direction = $filters['direction'] ?? 'asc';

        match ($filters['sort_by'] ?? 'name') {
            'name' => $query->orderBy(
                Person::query()->select('name')->whereColumn('people.id', 'students.person_id'),
                $direction,
            ),
            'course' => $query->orderBy(
                Course::query()->select('name')->whereColumn('courses.id', 'students.course_id'),
                $direction,
            ),
            'enrollment_number' => $query->orderBy('students.enrollment_number', $direction),
            'status' => $query->orderBy('students.status', $direction),
        };

        // Tie-breaking: without it, lines with the same value might be duplicated or go missing across pages.
        $query->orderBy('students.id');
    }
}
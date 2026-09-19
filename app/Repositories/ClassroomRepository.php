<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ClassroomRepository
{
    /**
     * List all classrooms with their subject count.
     *
     * @return Collection<int, Classroom>
     */
    public function all(): Collection
    {
        try {
            return Classroom::query()->withCount('classroomSubjects')->latest()->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list classrooms.', previous: $e);
        }
    }

    /**
     * Create a new classroom.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Classroom
    {
        try {
            return Classroom::query()->create($data);
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create classroom.', ['data' => $data], $e);
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
            return $classroom->classroomSubjects()->with(['subject', 'teacher.user'])->get();
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
}

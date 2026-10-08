<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class TeacherRepository
{
    public function __construct(private readonly PersonRepository $people) {}

    /**
     * List all teachers with their person.
     *
     * @return Collection<int, Teacher>
     */
    public function allWithPerson(): Collection
    {
        try {
            return Teacher::with('person:id,name')->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list teachers.', previous: $e);
        }
    }

    /**
     * List the teachers of the school with their person data, contacts and login e-mail.
     *
     * @return Collection<int, Teacher>
     */
    public function all(): Collection
    {
        try {
            return Teacher::query()
                ->with(['person.user:id,email', 'person.address', 'person.phones', 'person.emails'])
                ->get()
                ->sortBy('person.name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list teachers.', previous: $e);
        }
    }

    /**
     * Register a teacher together with their user account and person data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Teacher
    {
        try {
            return DB::transaction(function () use ($data): Teacher {
                $person = $this->people->createWithUser($data, 'teacher');

                return Teacher::query()->create([
                    'person_id' => $person->id,
                    'registration_number' => $data['registration_number'],
                    'qualification' => $data['qualification'] ?? null,
                ]);
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create teacher.', ['email' => $data['email'] ?? null], $e);
        }
    }

    /**
     * Update a teacher together with their user account and person data.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data): Teacher
    {
        try {
            return DB::transaction(function () use ($teacher, $data): Teacher {
                $this->people->update($teacher->person, $data);

                $teacher->update([
                    'registration_number' => $data['registration_number'],
                    'qualification' => $data['qualification'] ?? null,
                ]);

                return $teacher;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update teacher.', ['teacher_id' => $teacher->id], $e);
        }
    }
}

<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class TeacherRepository
{
    /**
     * List all teachers with their person.
     *
     * @return Collection<int, Teacher>
     */
    public function
    allWithPerson(): Collection
    {
        try {
            return Teacher::with('person:id,name')->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list teachers.', previous: $e);
        }
    }
}

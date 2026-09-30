<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class AcademicTermRepository
{
    /**
     * List all academic terms, newest first.
     *
     * @return Collection<int, AcademicTerm>
     */
    public function all(): Collection
    {
        try {
            return AcademicTerm::query()->withCount('classrooms')->orderByDesc('year')->orderByDesc('number')->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list academic terms.', previous: $e);
        }
    }

    /**
     * Create a new academic term.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AcademicTerm
    {
        try {
            return AcademicTerm::query()->create([...$data, 'name' => "{$data['year']}/{$data['number']}"]);
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create academic term.', ['data' => $data], $e);
        }
    }

    /**
     * Update an existing academic term.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(AcademicTerm $term, array $data): AcademicTerm
    {
        try {
            $term->update([...$data, 'name' => "{$data['year']}/{$data['number']}"]);

            return $term;
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update academic term.', ['academic_term_id' => $term->id, 'data' => $data], $e);
        }
    }
}

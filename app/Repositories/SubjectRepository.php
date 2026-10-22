<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubjectRepository
{
    /**
     * List the subjects of the school the user may see with their teacher, knowledge area and the classrooms
     * where the user is linked to them: every offering with classrooms.view-all, otherwise the ones they teach.
     *
     * @return Collection<int, Subject>
     */
    public function all(User $user): Collection
    {
        try {
            return Subject::query()
                ->visibleTo($user)
                ->with([
                    'teacher.person:id,name',
                    'knowledgeArea:id,name',
                    'classroomSubjects' => fn (HasMany $query) => $query
                        ->select(['id', 'subject_id', 'classroom_id'])
                        ->when(! $user->can('classrooms.view-all'), fn (Builder $query) => $query->where('teacher_id', $user->teacher?->id))
                        ->whereHas('classroom')
                        ->with('classroom:id,name')
                        ->orderBy('classroom_id'),
                ])
                ->orderBy('name')
                ->get();
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list subjects.', previous: $e);
        }
    }

    /**
     * Create a subject.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Subject
    {
        try {
            return DB::transaction(function () use ($data): Subject {
                $subject = Subject::query()->create($this->attributes($data));

                return $subject;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create subject.', ['data' => $data], $e);
        }
    }

    /**
     * Update a subject.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Subject $subject, array $data): Subject
    {
        try {
            return DB::transaction(function () use ($subject, $data): Subject {
                $subject->update($this->attributes($data));

                return $subject;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update subject.', ['subject_id' => $subject->id, 'data' => $data], $e);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'],
            'workload_hours' => $data['workload_hours'],
            'teacher_id' => $data['teacher_id'],
            'knowledge_area_id' => $data['knowledge_area_id'] ?? null,
        ];
    }
}

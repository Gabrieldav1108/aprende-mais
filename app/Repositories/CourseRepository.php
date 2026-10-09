<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class CourseRepository
{
    /**
     * List all courses with their subject and classroom counts.
     *
     * @return Collection<int, Course>
     */
    public function all(): Collection
    {
        try {
            return Course::query()
                ->with('subjects:id')
                ->withCount(['subjects', 'classrooms'])
                ->orderBy('name')
                ->get()
                ->each(fn (Course $course) => $this->exposeSubjectTerms($course));
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to list courses.', previous: $e);
        }
    }

    /**
     * Create a new course.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course
    {
        try {
            return DB::transaction(function () use ($data): Course {
                $course = Course::query()->create(Arr::except($data, 'subjects'));
                $course->subjects()->sync($this->subjectLinks($data['subjects'] ?? []));

                return $course;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to create course.', ['data' => $data], $e);
        }
    }

    /**
     * Update an existing course.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Course $course, array $data): Course
    {
        try {
            return DB::transaction(function () use ($course, $data): Course {
                $course->update(Arr::except($data, 'subjects'));
                $course->subjects()->sync($this->subjectLinks($data['subjects'] ?? []));

                return $course;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update course.', ['course_id' => $course->id, 'data' => $data], $e);
        }
    }

    /**
     * Expose the course's subjects with their term as `subject_terms`, replacing the loaded relation.
     */
    public function exposeSubjectTerms(Course $course): Course
    {
        $course->setAttribute('subject_terms', $course->loadMissing('subjects:id')->subjects->map(fn (Subject $subject) => [
            'subject_id' => $subject->id,
            'term' => $subject->pivot->suggested_term_number,
        ])->all());
        $course->unsetRelation('subjects');

        return $course;
    }

    /**
     * Build the sync payload (subject id => term) from the submitted subjects.
     *
     * @param  list<array{id: int, term: int}>  $subjects
     * @return array<int, array{suggested_term_number: int}>
     */
    private function subjectLinks(array $subjects): array
    {
        return collect($subjects)
            ->mapWithKeys(fn (array $subject) => [$subject['id'] => ['suggested_term_number' => $subject['term']]])
            ->all();
    }

    /**
     * Get the course's subject catalog.
     *
     * @return Collection<int, Subject>
     */
    public function subjectsFor(Course $course): Collection
    {
        try {
            return $course->subjects()
                ->orderBy('course_subject.suggested_term_number')
                ->orderBy('subjects.name')
                ->with('teacher.person:id,name')
                ->get()
                ->each(function (Subject $subject): void {
                    $subject->setAttribute('suggested_term_number', $subject->pivot->suggested_term_number);
                    $subject->makeHidden('pivot');
                });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to load course subjects.', ['course_id' => $course->id], $e);
        }
    }

    /**
     * Add a subject to the course catalog.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSubject(Course $course, array $data): Subject
    {
        try {
            return DB::transaction(function () use ($course, $data): Subject {
                $subject = Subject::query()->create([
                    'school_id' => $course->school_id,
                    'teacher_id' => $data['teacher_id'],
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'workload_hours' => $data['workload_hours'],
                ]);

                $course->subjects()->attach($subject->id, ['suggested_term_number' => $data['suggested_term_number'] ?? null]);

                return $subject;
            });
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to add subject to course.', ['course_id' => $course->id, 'data' => $data], $e);
        }
    }
}

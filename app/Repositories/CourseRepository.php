<?php

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
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
            return Course::query()->withCount(['subjects', 'classrooms'])->orderBy('name')->get();
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
            return Course::query()->create($data);
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
            $course->update($data);

            return $course;
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to update course.', ['course_id' => $course->id, 'data' => $data], $e);
        }
    }

    /**
     * Get the course's subject catalog.
     *
     * @return Collection<int, Subject>
     */
    public function subjectsFor(Course $course): Collection
    {
        try {
            return $course->subjects()->orderBy('suggested_term_number')->orderBy('name')->get();
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
            return $course->subjects()->create([...$data, 'school_id' => $course->school_id]);
        } catch (Throwable $e) {
            throw new RepositoryException('Failed to add subject to course.', ['course_id' => $course->id, 'data' => $data], $e);
        }
    }
}

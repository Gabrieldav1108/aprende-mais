<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => auth()->user()?->school_id ?? School::factory(),
            'teacher_id' => Teacher::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('???-###'),
            'workload_hours' => fake()->numberBetween(20, 200),
        ];
    }

    /**
     * Create the subject in the school of the course and link it to the course.
     */
    public function forCourse(Course|int $course, ?int $suggestedTermNumber = null): static
    {
        $courseId = $course instanceof Course ? $course->id : $course;

        return $this
            ->state(fn () => ['school_id' => Course::withoutGlobalScopes()->findOrFail($courseId)->school_id])
            ->afterCreating(function (Subject $subject) use ($courseId, $suggestedTermNumber): void {
                $subject->courses()->attach($courseId, ['suggested_term_number' => $suggestedTermNumber]);
            });
    }
}

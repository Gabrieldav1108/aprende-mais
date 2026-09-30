<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Subject;
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
            'course_id' => Course::factory(),
            'school_id' => fn (array $attributes) => Course::withoutGlobalScopes()->find($attributes['course_id'])->school_id,
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('???-###'),
            'workload_hours' => fake()->numberBetween(20, 200),
        ];
    }
}

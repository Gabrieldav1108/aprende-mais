<?php

namespace Database\Factories;

use App\Enums\ClassroomStatus;
use App\Enums\Shift;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
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
            'academic_term_id' => fn (array $attributes) => AcademicTerm::factory()->create([
                'school_id' => $attributes['school_id'],
            ])->id,
            'name' => fake()->word().' '.fake()->numberBetween(1, 9).fake()->randomLetter(),
            'code' => fake()->unique()->bothify('T-####'),
            'term_number' => fake()->numberBetween(1, 8),
            'shift' => fake()->randomElement(Shift::cases()),
            'status' => ClassroomStatus::Active,
            'capacity' => fake()->numberBetween(20, 60),
        ];
    }
}

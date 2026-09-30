<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('CUR-###'),
            'level' => fake()->randomElement(CourseLevel::cases()),
            'duration_terms' => fake()->numberBetween(4, 10),
            'status' => CourseStatus::Active,
        ];
    }
}

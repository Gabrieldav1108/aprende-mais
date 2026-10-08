<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'registration_number' => fake()->unique()->numerify('T#####'),
            'qualification' => fake()->jobTitle(),
        ];
    }

    /**
     * Create the teacher, and its person, in the given school.
     */
    public function inSchool(int $schoolId): static
    {
        return $this->state(fn () => ['person_id' => Person::factory()->inSchool($schoolId)]);
    }
}

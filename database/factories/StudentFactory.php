<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Person;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
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
            'course_id' => null,
            'enrollment_number' => fake()->unique()->numerify('A#####'),
            'status' => StudentStatus::Active,
        ];
    }

    /**
     * A student's user always has the student role.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Student $student): void {
            Role::findOrCreate(UserRole::Student->value);

            Person::withoutGlobalScopes()->find($student->person_id)->user->assignRole(UserRole::Student->value);
        });
    }

    /**
     * Create the student, and its person, in the given school.
     */
    public function inSchool(int $schoolId): static
    {
        return $this->state(fn () => ['person_id' => Person::factory()->inSchool($schoolId)]);
    }
}

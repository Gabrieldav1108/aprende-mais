<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state; use forActivity() to pick the activity.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'content' => fake()->paragraph(),
        ];
    }

    /**
     * Answer the given activity.
     */
    public function forActivity(Activity $activity): static
    {
        return $this->state(fn () => ['activity_id' => $activity->id]);
    }

    /**
     * Sent by the given student.
     */
    public function by(Student $student): static
    {
        return $this->state(fn () => ['student_id' => $student->id]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ClassroomSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state; use forClassroomSubject() to pick the classroom subject.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'due_at' => null,
            'sent_by_email' => false,
        ];
    }

    /**
     * Post the activity to the given classroom subject.
     */
    public function forClassroomSubject(ClassroomSubject $classroomSubject): static
    {
        return $this->state(fn () => ['classroom_subject_id' => $classroomSubject->id]);
    }
}

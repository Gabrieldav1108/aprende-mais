<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\ClassroomSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
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
            'body' => fake()->paragraph(),
            'sent_by_email' => false,
        ];
    }

    /**
     * Post the announcement to the given classroom subject.
     */
    public function forClassroomSubject(ClassroomSubject $classroomSubject): static
    {
        return $this->state(fn () => ['classroom_subject_id' => $classroomSubject->id]);
    }
}

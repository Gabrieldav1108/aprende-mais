<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
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
            'user_id' => User::factory(),
            'school_id' => fn (array $attributes) => User::find($attributes['user_id'])?->school_id,
            'registration_number' => fake()->unique()->numerify('T#####'),
            'qualification' => fake()->jobTitle(),
        ];
    }
}

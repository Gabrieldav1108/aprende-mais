<?php

namespace Database\Factories;

use App\Models\Classroom;
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
            'name' => fake()->word().' '.fake()->numberBetween(1, 9).fake()->randomLetter(),
            'school_year' => (string) fake()->year(),
            'shift' => fake()->randomElement(['morning', 'afternoon', 'evening']),
        ];
    }
}

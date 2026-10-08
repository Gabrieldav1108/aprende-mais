<?php

namespace Database\Factories;

use App\Models\Email;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Email>
 */
class EmailFactory extends Factory
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
            'address' => fake()->unique()->safeEmail(),
        ];
    }
}

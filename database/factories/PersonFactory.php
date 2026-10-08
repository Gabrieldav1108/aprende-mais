<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
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
            'school_id' => fn (array $attributes) => User::find($attributes['user_id'])->school_id,
            'name' => fn (array $attributes) => User::find($attributes['user_id'])->name,
            'cpf_cnpj' => fake()->unique()->numerify('###########'),
            'birth_date' => fake()->date(max: '-17 years'),
        ];
    }

    /**
     * Create the person, and its user, in the given school.
     */
    public function inSchool(int $schoolId): static
    {
        return $this->state(fn () => [
            'school_id' => $schoolId,
            'user_id' => User::factory()->state(['school_id' => $schoolId]),
        ]);
    }
}

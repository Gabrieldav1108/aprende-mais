<?php

namespace Database\Factories;

use App\Enums\TermStatus;
use App\Models\AcademicTerm;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2999);
        $number = fake()->numberBetween(1, 2);

        return [
            'school_id' => auth()->user()?->school_id ?? School::factory(),
            'name' => "{$year}/{$number}",
            'year' => $year,
            'number' => $number,
            'starts_at' => $number === 1 ? "{$year}-02-01" : "{$year}-08-01",
            'ends_at' => $number === 1 ? "{$year}-06-30" : "{$year}-12-15",
            'status' => TermStatus::Open,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\KnowledgeArea;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeArea>
 */
class KnowledgeAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => auth()->user()?->school_id ?? School::factory(),
            'name' => fake()->unique()->words(3, true),
            'code' => fake()->unique()->lexify('???'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zip_code' => fake()->numerify('########'),
            'street' => fake()->streetName(),
            'neighborhood' => fake()->word(),
            'city' => fake()->city(),
            'state' => fake()->lexify('??'),
            'ibge_code' => fake()->numerify('#######'),
            'ibge_state_code' => fake()->numerify('##'),
            'number' => fake()->buildingNumber(),
            'complement' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OccurrenceTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'contract_status' => null,
        ];
    }
}

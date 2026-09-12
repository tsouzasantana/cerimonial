<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'document' => fake()->numerify('###.###.###-##'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('###########'),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->bothify('SRV-###')),
            'price' => fake()->randomFloat(2, 100, 5000),
            'active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use Database\Factories\Concerns\GeneratesFakeDocuments;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    use GeneratesFakeDocuments;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'document' => $this->fakeCpf(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('1198#######'),
        ];
    }
}

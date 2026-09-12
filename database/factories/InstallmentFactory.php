<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'number' => 1,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'due_date' => fake()->dateTimeBetween('-10 days', '+30 days'),
            'status' => Installment::STATUS_PENDENTE,
        ];
    }
}

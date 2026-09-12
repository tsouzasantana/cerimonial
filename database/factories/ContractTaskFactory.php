<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'name' => fake()->sentence(3),
            'due_date' => fake()->dateTimeBetween('-10 days', '+30 days'),
            'status' => ContractTask::STATUS_A_INICIAR,
        ];
    }
}

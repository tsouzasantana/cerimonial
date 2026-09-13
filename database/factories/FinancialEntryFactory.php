<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\FinancialEntry;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialEntryFactory extends Factory
{
    protected $model = FinancialEntry::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 50, 3000),
            'status' => Installment::STATUS_PENDENTE,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'event_date' => fake()->dateTimeBetween('now', '+6 months'),
            'event_location' => fake()->address(),
            'status' => Contract::STATUS_RASCUNHO,
            'discount' => 0,
            'discount_type' => Contract::DISCOUNT_TYPE_FIXED,
            'discount_value' => 0,
        ];
    }
}

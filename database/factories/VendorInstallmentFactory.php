<?php

namespace Database\Factories;

use App\Models\Installment;
use App\Models\Vendor;
use App\Models\VendorInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorInstallmentFactory extends Factory
{
    protected $model = VendorInstallment::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'number' => 1,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'due_date' => fake()->dateTimeBetween('-10 days', '+30 days'),
            'status' => Installment::STATUS_PENDENTE,
        ];
    }
}

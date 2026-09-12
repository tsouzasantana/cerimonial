<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'vendor_service_type_id' => VendorServiceType::factory(),
            'name' => fake()->company(),
            'document' => fake()->numerify('##.###.###/0001-##'),
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ];
    }
}

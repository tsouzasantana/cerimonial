<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Database\Factories\Concerns\GeneratesFakeDocuments;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    use GeneratesFakeDocuments;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'vendor_service_type_id' => VendorServiceType::factory(),
            'name' => fake()->company(),
            'document' => $this->fakeCnpj(),
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ];
    }
}

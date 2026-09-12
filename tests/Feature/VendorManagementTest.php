<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_vendor_with_an_existing_service_type(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $type = VendorServiceType::factory()->create();

        $this->actingAs($user)->post(route('vendors.store', $contract), [
            'name' => 'Buffet Sabor',
            'document' => '12.345.678/0001-99',
            'vendor_service_type_id' => $type->id,
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ])->assertRedirect();

        $this->assertDatabaseHas('vendors', [
            'contract_id' => $contract->id,
            'name' => 'Buffet Sabor',
            'vendor_service_type_id' => $type->id,
        ]);
    }

    public function test_admin_can_create_a_vendor_typing_a_new_service_type(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $this->actingAs($user)->post(route('vendors.store', $contract), [
            'name' => 'Chaveiro Rápido',
            'new_service_type' => 'Segurança do local',
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ])->assertRedirect();

        $type = VendorServiceType::where('name', 'Segurança do local')->firstOrFail();
        $this->assertDatabaseHas('vendors', ['name' => 'Chaveiro Rápido', 'vendor_service_type_id' => $type->id]);
    }

    public function test_vendor_requires_a_service_type_or_a_new_one(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $this->actingAs($user)->post(route('vendors.store', $contract), [
            'name' => 'Sem tipo',
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ])->assertSessionHasErrors(['vendor_service_type_id', 'new_service_type']);
    }

    public function test_admin_can_update_vendor_status_and_payment_status(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create([
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ]);

        $this->actingAs($user)->put(route('vendors.update', [$vendor->contract, $vendor]), [
            'name' => $vendor->name,
            'document' => $vendor->document,
            'vendor_service_type_id' => $vendor->vendor_service_type_id,
            'notes' => 'Pagamento confirmado',
            'status' => Vendor::STATUS_PRESTADO,
            'payment_status' => Vendor::PAYMENT_INTEGRAL,
        ])->assertRedirect();

        $vendor->refresh();
        $this->assertSame(Vendor::STATUS_PRESTADO, $vendor->status);
        $this->assertSame(Vendor::PAYMENT_INTEGRAL, $vendor->payment_status);
        $this->assertSame('Pagamento confirmado', $vendor->notes);
    }

    public function test_vendor_can_be_inactivated_and_restored(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $this->actingAs($user)->delete(route('vendors.destroy', [$vendor->contract, $vendor]))
            ->assertRedirect();
        $this->assertSoftDeleted($vendor);

        $this->actingAs($user)->post(route('vendors.restore', [$vendor->contract, $vendor->id]))
            ->assertRedirect();
        $this->assertNotSoftDeleted($vendor);
    }
}

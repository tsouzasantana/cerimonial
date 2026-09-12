<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorServiceTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_vendor_service_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('vendor-service-types.store'), [
            'name' => 'Cerimonialista',
        ])->assertRedirect(route('vendor-service-types.index'));

        $this->assertDatabaseHas('vendor_service_types', ['name' => 'Cerimonialista']);
    }

    public function test_a_type_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $type = VendorServiceType::factory()->create();
        Vendor::factory()->create(['vendor_service_type_id' => $type->id]);

        $this->actingAs($user)->delete(route('vendor-service-types.destroy', $type))
            ->assertRedirect(route('vendor-service-types.index'));

        $this->assertDatabaseHas('vendor_service_types', ['id' => $type->id]);
    }

    public function test_an_unused_type_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $type = VendorServiceType::factory()->create();

        $this->actingAs($user)->delete(route('vendor-service-types.destroy', $type))
            ->assertRedirect(route('vendor-service-types.index'));

        $this->assertDatabaseMissing('vendor_service_types', ['id' => $type->id]);
    }
}

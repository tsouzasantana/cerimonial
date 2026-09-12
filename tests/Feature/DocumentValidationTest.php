<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_with_invalid_cpf_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', [
            'name' => 'Cliente Teste',
            'document' => '111.111.111-11',
        ])->assertSessionHasErrors('document');

        $this->assertDatabaseMissing('clients', ['name' => 'Cliente Teste']);
    }

    public function test_client_with_valid_cpf_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', [
            'name' => 'Cliente Teste',
            'document' => '529.982.247-25',
        ])->assertSessionDoesntHaveErrors('document');

        $client = Client::where('name', 'Cliente Teste')->firstOrFail();
        $this->assertSame('529.982.247-25', $client->document);
        $this->assertDatabaseMissing('clients', ['document' => '529.982.247-25']);
    }

    public function test_client_with_valid_cnpj_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', [
            'name' => 'Buffet Cliente PJ',
            'document' => '11.222.333/0001-81',
        ])->assertSessionDoesntHaveErrors('document');
    }

    public function test_client_document_is_optional(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', [
            'name' => 'Sem Documento',
        ])->assertSessionDoesntHaveErrors('document');
    }

    public function test_vendor_with_invalid_cnpj_is_rejected(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $type = VendorServiceType::factory()->create();

        $this->actingAs($user)->post(route('vendors.store', $contract), [
            'name' => 'Fornecedor Teste',
            'document' => '11.222.333/0001-82',
            'vendor_service_type_id' => $type->id,
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ])->assertSessionHasErrors('document');
    }

    public function test_client_can_verify_a_valid_but_never_registered_public_token_cpf(): void
    {
        // Sanity check that the public CPF gate (a plain equality check, not
        // checksum validation) still works unaffected by the new rule.
        $client = Client::factory()->create(['document' => '123.456.789-09']);
        $contract = Contract::factory()->create(['client_id' => $client->id]);

        $this->post(route('public.verify', $contract->public_token), ['document' => '123.456.789-09'])
            ->assertRedirect(route('public.show', $contract->public_token));
    }
}

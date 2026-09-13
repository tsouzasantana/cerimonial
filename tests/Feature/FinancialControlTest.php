<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\FinancialEntry;
use App\Models\Installment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialControlTest extends TestCase
{
    use RefreshDatabase;

    private function contractWithClientCpf(string $cpf = '123.456.789-09'): Contract
    {
        $client = Client::factory()->create(['document' => $cpf]);

        return Contract::factory()->create(['client_id' => $client->id]);
    }

    public function test_admin_can_set_a_vendor_contract_value(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $this->actingAs($user)->put(route('vendors.update', [$vendor->contract, $vendor]), [
            'name' => $vendor->name,
            'document' => $vendor->document,
            'contract_value' => '3500.00',
            'vendor_service_type_id' => $vendor->vendor_service_type_id,
            'status' => $vendor->status,
            'payment_status' => $vendor->payment_status,
        ])->assertRedirect();

        $this->assertSame('3500.00', $vendor->fresh()->contract_value);
    }

    public function test_admin_can_add_a_vendor_installment(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $this->actingAs($user)->post(route('vendor-installments.store', [$vendor->contract, $vendor]), [
            'number' => 1,
            'amount' => '500.00',
            'due_date' => '2027-02-01',
            'status' => Installment::STATUS_PENDENTE,
        ])->assertRedirect();

        $this->assertDatabaseHas('vendor_installments', [
            'vendor_id' => $vendor->id,
            'amount' => 500,
        ]);
    }

    public function test_admin_can_generate_vendor_installments_in_batch(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $this->actingAs($user)->post(route('vendor-installments.store-batch', [$vendor->contract, $vendor]), [
            'total_amount' => '900.00',
            'installment_count' => 3,
            'first_due_date' => '2027-01-10',
            'interval_months' => 1,
        ])->assertRedirect();

        $installments = $vendor->installments()->orderBy('number')->get();
        $this->assertCount(3, $installments);
        $this->assertEquals('300.00', $installments[0]->amount);
    }

    public function test_vendor_installment_from_another_contract_is_rejected(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $otherContract = Contract::factory()->create();

        $this->actingAs($user)->post(route('vendor-installments.store', [$otherContract, $vendor]), [
            'number' => 1,
            'amount' => '100',
            'due_date' => '2027-01-01',
            'status' => Installment::STATUS_PENDENTE,
        ])->assertNotFound();
    }

    public function test_installment_from_a_different_vendor_cannot_be_updated_through_this_vendor(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $otherVendor = Vendor::factory()->create(['contract_id' => $vendor->contract_id]);
        $installment = VendorInstallment::factory()->create(['vendor_id' => $otherVendor->id]);

        $this->actingAs($user)->put(route('vendor-installments.update', [$vendor->contract, $vendor, $installment]), [
            'number' => $installment->number,
            'amount' => $installment->amount,
            'due_date' => $installment->due_date->format('Y-m-d'),
            'status' => Installment::STATUS_PAGO,
        ])->assertNotFound();
    }

    public function test_admin_can_add_a_financial_entry_linked_to_a_vendor(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $this->actingAs($user)->post(route('financial-entries.store', $vendor->contract), [
            'vendor_id' => $vendor->id,
            'description' => 'Sinal do buffet',
            'amount' => '1200.00',
            'status' => Installment::STATUS_PENDENTE,
        ])->assertRedirect();

        $this->assertDatabaseHas('financial_entries', [
            'contract_id' => $vendor->contract_id,
            'vendor_id' => $vendor->id,
            'description' => 'Sinal do buffet',
        ]);
    }

    public function test_financial_entry_linked_to_a_vendor_from_another_contract_is_rejected(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $vendorOfAnotherContract = Vendor::factory()->create();

        $this->actingAs($user)->post(route('financial-entries.store', $contract), [
            'vendor_id' => $vendorOfAnotherContract->id,
            'description' => 'Gasto qualquer',
            'amount' => '100',
            'status' => Installment::STATUS_PENDENTE,
        ])->assertNotFound();
    }

    public function test_marking_a_financial_entry_as_paid_without_a_date_fills_it_automatically(): void
    {
        $user = User::factory()->create();
        $entry = FinancialEntry::factory()->create(['status' => Installment::STATUS_PENDENTE]);

        $this->actingAs($user)->put(route('financial-entries.update', [$entry->contract, $entry]), [
            'description' => $entry->description,
            'amount' => $entry->amount,
            'status' => Installment::STATUS_PAGO,
        ])->assertRedirect();

        $this->assertNotNull($entry->fresh()->paid_at);
    }

    public function test_financial_control_tab_shows_vendor_installments_and_manual_entries(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['name' => 'Buffet da Serra']);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 777]);
        FinancialEntry::factory()->create(['contract_id' => $vendor->contract_id, 'description' => 'Aluguel de mesas']);

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $vendor->contract, 'tab' => 'financeiro']));

        $response->assertOk();
        $response->assertSee('Buffet da Serra');
        $response->assertSee('777,00');
        $response->assertSee('Aluguel de mesas');
    }

    public function test_client_can_add_a_vendor_installment_and_a_financial_entry_via_public_portal(): void
    {
        $contract = $this->contractWithClientCpf();
        $vendor = Vendor::factory()->create(['contract_id' => $contract->id]);
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->post(route('public.vendors.installments.store', ['token' => $contract->public_token, 'vendor' => $vendor]), [
            'number' => 1,
            'amount' => '250.00',
            'due_date' => '2027-03-01',
            'status' => Installment::STATUS_PENDENTE,
        ])->assertRedirect();

        $this->post(route('public.financial-entries.store', $contract->public_token), [
            'description' => 'Compra de lembrancinhas',
            'amount' => '400.00',
            'status' => Installment::STATUS_PENDENTE,
        ])->assertRedirect();

        $this->assertDatabaseHas('vendor_installments', ['vendor_id' => $vendor->id, 'amount' => 250]);
        $this->assertDatabaseHas('financial_entries', ['contract_id' => $contract->id, 'description' => 'Compra de lembrancinhas']);

        $entry = FinancialEntry::where('contract_id', $contract->id)->firstOrFail();
        $this->assertSame('client', $entry->updated_by_type);
    }

    public function test_public_portal_renders_vendor_installments_and_financial_tab_when_data_exists(): void
    {
        $contract = $this->contractWithClientCpf();
        $vendor = Vendor::factory()->create(['contract_id' => $contract->id, 'name' => 'Buffet do Portal']);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 321]);
        FinancialEntry::factory()->create(['contract_id' => $contract->id, 'vendor_id' => $vendor->id, 'description' => 'Gasto do portal']);

        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $fornecedores = $this->get(route('public.show', ['token' => $contract->public_token, 'tab' => 'fornecedores']));
        $fornecedores->assertOk();
        $fornecedores->assertSee('Buffet do Portal');
        $fornecedores->assertSee('321,00');

        $financeiro = $this->get(route('public.show', ['token' => $contract->public_token, 'tab' => 'financeiro']));
        $financeiro->assertOk();
        $financeiro->assertSee('Gasto do portal');
        $financeiro->assertSee('321,00');
    }

    public function test_guest_cannot_add_a_financial_entry(): void
    {
        $contract = Contract::factory()->create();

        $this->post(route('financial-entries.store', $contract), [
            'description' => 'Gasto',
            'amount' => '10',
            'status' => Installment::STATUS_PENDENTE,
        ])->assertRedirect(route('login'));
    }
}

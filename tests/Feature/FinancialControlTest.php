<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentType;
use App\Models\FinancialEntry;
use App\Models\Installment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInstallment;
use App\Models\VendorServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
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

    public function test_the_add_installment_form_renders_every_field_the_request_requires(): void
    {
        // Regression test: the form was missing the "status" select even
        // though VendorInstallmentRequest requires it, so every real
        // submission through the UI failed validation while the
        // controller-level tests above (which post a complete payload
        // directly) never caught it.
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $vendor->contract, 'tab' => 'fornecedores']));

        $response->assertOk();
        $response->assertSee("id=\"vendor-{$vendor->id}-installment-number\"", false);
        $response->assertSee("id=\"vendor-{$vendor->id}-installment-amount\"", false);
        $response->assertSee("id=\"vendor-{$vendor->id}-installment-due_date\"", false);
        $response->assertSee("id=\"vendor-{$vendor->id}-installment-status\"", false);
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

    public function test_a_validation_error_on_vendor_installment_is_shown_and_expands_the_section(): void
    {
        // Renders the partial directly instead of a real POST + GET: the
        // "array" session driver used in tests doesn't carry flashed
        // errors/old input across separate requests the way the database
        // driver does in production, so a redirect-back round trip can't be
        // exercised here. This isolates exactly what matters — that the
        // Blade conditionals correctly show the error and expand the
        // section — from that testing-only session limitation.
        $vendor = Vendor::factory()->create();

        $session = app('session.store');
        $session->put('_old_input', ['_vendor_id' => (string) $vendor->id]);
        app('request')->setLaravelSession($session);

        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['amount' => ['O campo valor é obrigatório.']]));

        $html = view('contracts._vendors', [
            'contract' => $vendor->contract,
            'isPublic' => false,
            'documentTypes' => DocumentType::all(),
            'vendorServiceTypes' => VendorServiceType::all(),
            'errors' => $errors,
        ])->render();

        $this->assertStringContainsString('showInstallments: true', $html);
        $this->assertStringContainsString('O campo valor é obrigatório.', $html);
    }

    public function test_each_vendors_installment_amount_field_has_a_unique_id(): void
    {
        // Regression test: the "Valor" field's id used to default to the
        // component's name ("amount"/"total_amount"), so with two or more
        // vendors on the same contract every vendor's form shared the same
        // id. The browser only updates the FIRST element with a given id,
        // so typing an amount for any vendor other than the first silently
        // left that vendor's own hidden "amount" input empty, failing
        // validation with no clue why.
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $vendorOne = Vendor::factory()->create(['contract_id' => $contract->id]);
        $vendorTwo = Vendor::factory()->create(['contract_id' => $contract->id]);

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores']));

        $response->assertOk();
        $response->assertSee("id=\"vendor-{$vendorOne->id}-installment-amount\"", false);
        $response->assertSee("id=\"vendor-{$vendorTwo->id}-installment-amount\"", false);
        $response->assertSee("id=\"vendor-{$vendorOne->id}-total_amount\"", false);
        $response->assertSee("id=\"vendor-{$vendorTwo->id}-total_amount\"", false);
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

    public function test_marking_a_financial_entry_as_paid_without_a_date_does_not_fill_it_automatically(): void
    {
        $user = User::factory()->create();
        $entry = FinancialEntry::factory()->create(['status' => Installment::STATUS_PENDENTE, 'paid_at' => null]);

        $this->actingAs($user)->put(route('financial-entries.update', [$entry->contract, $entry]), [
            'description' => $entry->description,
            'amount' => $entry->amount,
            'status' => Installment::STATUS_PAGO,
        ])->assertRedirect();

        $this->assertNull($entry->fresh()->paid_at);
    }

    public function test_marking_a_financial_entry_as_paid_with_a_chosen_date_keeps_that_date(): void
    {
        $user = User::factory()->create();
        $entry = FinancialEntry::factory()->create(['status' => Installment::STATUS_PENDENTE, 'paid_at' => null]);

        $this->actingAs($user)->put(route('financial-entries.update', [$entry->contract, $entry]), [
            'description' => $entry->description,
            'amount' => $entry->amount,
            'status' => Installment::STATUS_PAGO,
            'paid_at' => '2026-01-10',
        ])->assertRedirect();

        $this->assertSame('2026-01-10', $entry->fresh()->paid_at->format('Y-m-d'));
    }

    public function test_financial_control_tab_shows_vendor_installments_manual_entries_and_contract_installments(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['name' => 'Buffet da Serra']);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 777]);
        FinancialEntry::factory()->create(['contract_id' => $vendor->contract_id, 'description' => 'Aluguel de mesas']);
        Installment::factory()->create(['contract_id' => $vendor->contract_id, 'number' => 1, 'amount' => 1500]);

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $vendor->contract, 'tab' => 'financeiro']));

        $response->assertOk();
        $response->assertSee('Buffet da Serra');
        $response->assertSee('777,00');
        $response->assertSee('Aluguel de mesas');
        $response->assertSee('1.500,00');
        $response->assertSee(config('cerimonial.company_name', 'Assessoria'));
    }

    public function test_financeiro_tab_appears_after_fornecedores_and_before_atividades(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $response = $this->actingAs($user)->get(route('contracts.show', $contract));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertGreaterThan(strpos($content, 'Fornecedores'), strpos($content, 'Controle financeiro'));
        $this->assertGreaterThan(strpos($content, 'Controle financeiro'), strpos($content, 'Atividades'));
    }

    public function test_public_portal_shows_financeiro_tab_after_fornecedores(): void
    {
        $contract = $this->contractWithClientCpf();
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $response = $this->get(route('public.show', $contract->public_token));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertGreaterThan(strpos($content, 'Fornecedores'), strpos($content, 'Controle financeiro'));
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

    public function test_marking_a_vendor_installment_as_paid_without_a_date_does_not_fill_it_automatically(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $installment = VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'paid_at' => null]);

        $this->actingAs($user)->put(route('vendor-installments.update', [$vendor->contract, $vendor, $installment]), [
            'number' => $installment->number,
            'amount' => $installment->amount,
            'due_date' => $installment->due_date->format('Y-m-d'),
            'status' => Installment::STATUS_PAGO,
        ])->assertRedirect();

        $this->assertNull($installment->fresh()->paid_at);
    }

    public function test_marking_a_vendor_installment_as_paid_with_a_chosen_date_keeps_that_date(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $installment = VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'paid_at' => null]);

        $this->actingAs($user)->put(route('vendor-installments.update', [$vendor->contract, $vendor, $installment]), [
            'number' => $installment->number,
            'amount' => $installment->amount,
            'due_date' => $installment->due_date->format('Y-m-d'),
            'status' => Installment::STATUS_PAGO,
            'paid_at' => '2026-02-05',
        ])->assertRedirect();

        $this->assertSame('2026-02-05', $installment->fresh()->paid_at->format('Y-m-d'));
    }

    public function test_marking_a_contract_installment_as_paid_without_a_date_does_not_fill_it_automatically(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $installment = Installment::factory()->create(['contract_id' => $contract->id, 'paid_at' => null]);

        $this->actingAs($user)->put(route('installments.update', [$contract, $installment]), [
            'number' => $installment->number,
            'amount' => $installment->amount,
            'due_date' => $installment->due_date->format('Y-m-d'),
            'status' => Installment::STATUS_PAGO,
        ])->assertRedirect();

        $this->assertNull($installment->fresh()->paid_at);
    }

    public function test_vendor_paid_total_remaining_and_uninvoiced_balance_are_computed_from_installments(): void
    {
        $vendor = Vendor::factory()->create(['contract_value' => 1000]);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 300, 'status' => Installment::STATUS_PAGO]);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 200, 'status' => Installment::STATUS_PENDENTE]);

        $vendor->refresh();

        $this->assertSame(300.0, $vendor->paidTotal());
        $this->assertSame(700.0, $vendor->remainingBalance());
        $this->assertSame(500.0, $vendor->uninvoicedBalance());
    }

    public function test_vendor_header_shows_paid_and_remaining_amounts(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['contract_value' => 1000, 'name' => 'Decoração Encantada']);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 400, 'status' => Installment::STATUS_PAGO]);

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $vendor->contract, 'tab' => 'fornecedores']));

        $response->assertOk();
        $response->assertSee('Pago: R$ 400,00');
        $response->assertSee('A pagar: R$ 600,00');
    }

    public function test_financial_tab_shows_pending_balance_row_for_uninvoiced_vendor_amount(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['contract_value' => 1000, 'name' => 'Fotografia Instantes']);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 300]);

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $vendor->contract, 'tab' => 'financeiro']));

        $response->assertOk();
        $response->assertSee('Saldo a lançar');
        $response->assertSee('700,00');
    }

    public function test_financial_tab_hides_pending_balance_row_once_fully_itemized(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['contract_value' => 1000, 'name' => 'Buffet Total']);
        VendorInstallment::factory()->create(['vendor_id' => $vendor->id, 'amount' => 1000]);

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $vendor->contract, 'tab' => 'financeiro']));

        $response->assertOk();
        $response->assertDontSee('Saldo a lançar');
    }

    public function test_financial_tab_offers_edit_modals_for_vendor_and_contract_installments(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $vendorInstallment = VendorInstallment::factory()->create(['vendor_id' => $vendor->id]);
        $contractInstallment = Installment::factory()->create(['contract_id' => $vendor->contract_id, 'number' => 1]);

        $response = $this->actingAs($user)->get(route('contracts.show', ['contract' => $vendor->contract, 'tab' => 'financeiro']));

        $response->assertOk();
        $response->assertSee("edit-vendor-installment-{$vendorInstallment->id}", false);
        $response->assertSee("edit-contract-installment-{$contractInstallment->id}", false);
    }

    public function test_public_portal_financial_tab_does_not_offer_contract_installment_edit_modal(): void
    {
        $contract = $this->contractWithClientCpf();
        $contractInstallment = Installment::factory()->create(['contract_id' => $contract->id, 'number' => 1]);
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $response = $this->get(route('public.show', ['token' => $contract->public_token, 'tab' => 'financeiro']));

        $response->assertOk();
        $response->assertDontSee("edit-contract-installment-{$contractInstallment->id}", false);
    }
}

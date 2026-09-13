<?php

namespace Tests\Feature;

use App\Mail\ContractPdfMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\OccurrenceType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_an_item_recalculates_contract_totals(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['discount' => 50]);
        $service = Service::factory()->create(['price' => 200]);

        $this->actingAs($user)->post(route('contract-items.store', $contract), [
            'service_id' => $service->id,
            'quantity' => 3,
        ])->assertRedirect(route('contracts.show', $contract));

        $contract->refresh();
        $this->assertEquals(600, $contract->subtotal);
        $this->assertEquals(550, $contract->total);
    }

    public function test_removing_an_item_recalculates_contract_totals(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $service = Service::factory()->create(['price' => 100]);
        $item = $contract->items()->create([
            'service_id' => $service->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total_price' => 200,
        ]);
        $contract->recalculateTotals();

        $this->actingAs($user)->delete(route('contract-items.destroy', [$contract, $item]))
            ->assertRedirect(route('contracts.show', $contract));

        $contract->refresh();
        $this->assertEquals(0, $contract->subtotal);
    }

    public function test_registering_an_occurrence_updates_contract_status(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['status' => Contract::STATUS_RASCUNHO]);
        $type = OccurrenceType::factory()->create(['contract_status' => Contract::STATUS_ATIVO]);

        $this->actingAs($user)->post(route('occurrences.store', $contract), [
            'occurrence_type_id' => $type->id,
            'occurrence_date' => now()->format('Y-m-d'),
            'description' => 'Contrato assinado pelo cliente',
        ])->assertRedirect(route('contracts.show', ['contract' => $contract, 'tab' => 'ocorrencias']));

        $this->assertEquals(Contract::STATUS_ATIVO, $contract->fresh()->status);
    }

    public function test_generating_pdf_stores_file_and_updates_contract(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $this->actingAs($user)->post(route('contracts.pdf', $contract))
            ->assertRedirect(route('contracts.show', $contract));

        $contract->refresh();
        $this->assertNotNull($contract->pdf_path);
        Storage::disk('local')->assertExists($contract->pdf_path);
    }

    public function test_sending_contract_email_requires_client_email(): void
    {
        Storage::fake('local');
        Mail::fake();
        $user = User::factory()->create();
        $client = Client::factory()->create(['email' => null]);
        $contract = Contract::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->post(route('contracts.send-email', $contract))
            ->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_sending_contract_email_generates_pdf_if_missing_and_sends_mail(): void
    {
        Storage::fake('local');
        Mail::fake();
        $user = User::factory()->create();
        $client = Client::factory()->create(['email' => 'cliente@example.com']);
        $contract = Contract::factory()->create(['client_id' => $client->id]);

        $this->actingAs($user)->post(route('contracts.send-email', $contract))
            ->assertRedirect(route('contracts.show', $contract));

        Mail::assertSent(ContractPdfMail::class);
        $this->assertNotNull($contract->fresh()->pdf_path);
    }
}

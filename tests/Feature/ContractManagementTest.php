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
        $contract = Contract::factory()->create(['discount_type' => 'fixed', 'discount_value' => 50]);
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

    public function test_updating_discount_as_fixed_amount_recalculates_the_total(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $service = Service::factory()->create(['price' => 1000]);
        $contract->items()->create(['service_id' => $service->id, 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000]);
        $contract->recalculateTotals();

        $this->actingAs($user)->patch(route('contracts.update-discount', $contract), [
            'discount_type' => 'fixed',
            'discount_value_fixed' => '150',
            'discount_value_percentage' => '0',
        ])->assertRedirect(route('contracts.show', ['contract' => $contract, 'tab' => 'resumo']));

        $contract->refresh();
        $this->assertSame('150.00', $contract->discount);
        $this->assertSame('850.00', $contract->total);
    }

    public function test_updating_discount_as_percentage_recalculates_the_total(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $service = Service::factory()->create(['price' => 1000]);
        $contract->items()->create(['service_id' => $service->id, 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000]);
        $contract->recalculateTotals();

        $this->actingAs($user)->patch(route('contracts.update-discount', $contract), [
            'discount_type' => 'percentage',
            'discount_value_fixed' => '0',
            'discount_value_percentage' => '10',
        ])->assertSessionDoesntHaveErrors();

        $contract->refresh();
        $this->assertSame('100.00', $contract->discount);
        $this->assertSame('900.00', $contract->total);
    }

    public function test_percentage_discount_stays_in_sync_when_items_change_afterward(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['discount_type' => 'percentage', 'discount_value' => 10]);
        $contract->recalculateTotals();
        $service = Service::factory()->create(['price' => 500]);

        $this->actingAs($user)->post(route('contract-items.store', $contract), [
            'service_id' => $service->id,
            'quantity' => 2,
        ])->assertRedirect(route('contracts.show', $contract));

        $contract->refresh();
        $this->assertSame('1000.00', $contract->subtotal);
        $this->assertSame('100.00', $contract->discount);
        $this->assertSame('900.00', $contract->total);
    }

    public function test_percentage_discount_above_100_is_rejected(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $this->actingAs($user)->patch(route('contracts.update-discount', $contract), [
            'discount_type' => 'percentage',
            'discount_value_fixed' => '0',
            'discount_value_percentage' => '150',
        ])->assertSessionHasErrors('discount_value_percentage');
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

        Mail::assertQueued(ContractPdfMail::class);
        $this->assertNotNull($contract->fresh()->pdf_path);
    }

    public function test_sending_contract_email_shows_friendly_error_when_queueing_fails(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $client = Client::factory()->create(['email' => 'cliente@example.com']);
        $contract = Contract::factory()->create(['client_id' => $client->id]);

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('queue')->once()->andThrow(new \RuntimeException('Falha ao gravar na fila'));

        $this->actingAs($user)->post(route('contracts.send-email', $contract))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}

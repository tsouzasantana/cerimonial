<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\Installment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_dashboard_loads_with_expected_sections_and_figures(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $contract = Contract::factory()->create(['client_id' => $client->id, 'status' => Contract::STATUS_ATIVO]);

        Installment::factory()->create([
            'contract_id' => $contract->id,
            'status' => Installment::STATUS_PAGO,
            'amount' => 500,
            'paid_at' => now(),
        ]);

        ContractTask::factory()->create([
            'contract_id' => $contract->id,
            'status' => ContractTask::STATUS_A_INICIAR,
            'due_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Financeiro');
        $response->assertSee('Contratos');
        $response->assertSee('Checklist');
        $response->assertSee('Clientes');
        $response->assertSee('R$ 500,00');
    }

    public function test_dashboard_shows_projected_cash_flow_for_pending_installments(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $contract = Contract::factory()->create(['client_id' => $client->id]);

        Installment::factory()->create([
            'contract_id' => $contract->id,
            'status' => Installment::STATUS_PENDENTE,
            'amount' => 300,
            'due_date' => now()->startOfMonth(),
        ]);

        Installment::factory()->create([
            'contract_id' => $contract->id,
            'status' => Installment::STATUS_ATRASADO,
            'amount' => 200,
            'due_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Fluxo de caixa projetado');
        $response->assertSee('R$ 500,00');
    }

    public function test_checklist_report_only_lists_contracts_with_overdue_tasks(): void
    {
        $user = User::factory()->create();

        $lateClient = Client::factory()->create(['name' => 'Cliente Atrasado']);
        $late = Contract::factory()->create(['client_id' => $lateClient->id]);
        ContractTask::factory()->create([
            'contract_id' => $late->id,
            'status' => ContractTask::STATUS_A_INICIAR,
            'due_date' => now()->subDays(3),
        ]);

        $onTrackClient = Client::factory()->create(['name' => 'Cliente Em Dia']);
        $onTrack = Contract::factory()->create(['client_id' => $onTrackClient->id]);
        ContractTask::factory()->create([
            'contract_id' => $onTrack->id,
            'status' => ContractTask::STATUS_CONCLUIDA,
            'due_date' => now()->subDays(3),
        ]);
        ContractTask::factory()->create([
            'contract_id' => $onTrack->id,
            'status' => ContractTask::STATUS_A_INICIAR,
            'due_date' => now()->addDays(3),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('checklist', function ($checklist) use ($late, $onTrack) {
            $ids = $checklist['contratos_com_atraso']->pluck('id');

            return $ids->contains($late->id) && ! $ids->contains($onTrack->id);
        });
    }
}

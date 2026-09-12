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
}

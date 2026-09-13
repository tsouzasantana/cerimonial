<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_active_contracts_and_client_counts(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Contract::factory()->create(['client_id' => $client->id, 'status' => Contract::STATUS_ATIVO]);
        Contract::factory()->create(['client_id' => $client->id, 'status' => Contract::STATUS_CANCELADO]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('activeContracts', 1);
        $response->assertViewHas('totalClients', 1);
    }

    public function test_dashboard_lists_upcoming_events_but_not_past_ones(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Cliente do Evento Futuro']);
        $upcoming = Contract::factory()->create([
            'client_id' => $client->id,
            'status' => Contract::STATUS_ATIVO,
            'event_date' => now()->addDays(10),
        ]);

        $pastClient = Client::factory()->create(['name' => 'Cliente do Evento Passado']);
        Contract::factory()->create([
            'client_id' => $pastClient->id,
            'status' => Contract::STATUS_ATIVO,
            'event_date' => now()->subDays(10),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Cliente do Evento Futuro');
        $response->assertDontSee('Cliente do Evento Passado');
        $response->assertViewHas('upcomingEvents', fn ($events) => $events->pluck('id')->contains($upcoming->id));
    }

    public function test_dashboard_lists_overdue_installments_but_not_paid_or_future_ones(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $contract = Contract::factory()->create(['client_id' => $client->id]);

        $overdue = Installment::factory()->create([
            'contract_id' => $contract->id,
            'status' => Installment::STATUS_PENDENTE,
            'due_date' => now()->subDays(5),
        ]);

        Installment::factory()->create([
            'contract_id' => $contract->id,
            'status' => Installment::STATUS_PAGO,
            'due_date' => now()->subDays(5),
            'paid_at' => now(),
        ]);

        Installment::factory()->create([
            'contract_id' => $contract->id,
            'status' => Installment::STATUS_PENDENTE,
            'due_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('overdueInstallments', function ($installments) use ($overdue) {
            return $installments->count() === 1 && $installments->first()->id === $overdue->id;
        });
    }
}

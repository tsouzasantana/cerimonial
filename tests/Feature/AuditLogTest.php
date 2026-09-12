<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
    }

    public function test_admin_actions_are_recorded_and_listed_on_global_page(): void
    {
        $user = User::factory()->create(['name' => 'Fulano Admin']);
        $this->actingAs($user);

        Client::factory()->create(['name' => 'Cliente Auditado']);

        $response = $this->actingAs($user)->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Cliente Auditado');
        $response->assertSee('Fulano Admin');
        $response->assertSee('Equipe');
    }

    public function test_global_page_can_be_filtered_by_actor_type(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Client::factory()->create(['name' => 'Via Admin']);

        $response = $this->actingAs($user)->get(route('audit-logs.index', ['actor_type' => 'client']));

        $response->assertOk();
        $response->assertDontSee('Via Admin');
    }

    public function test_contract_activity_tab_shows_only_that_contracts_logs(): void
    {
        $user = User::factory()->create();

        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $contractA = Contract::factory()->create(['client_id' => $clientA->id, 'notes' => 'Original A']);
        $contractB = Contract::factory()->create(['client_id' => $clientB->id, 'notes' => 'Original B']);

        $this->actingAs($user);
        $contractA->update(['notes' => 'Alterado A']);
        $contractB->update(['notes' => 'Alterado B']);

        $response = $this->actingAs($user)->get(route('contracts.show', $contractA).'?tab=atividades');

        $response->assertOk();
        $response->assertSee('Contrato');
        $response->assertDontSee('Alterado B');
    }
}

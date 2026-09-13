<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractTask;
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

    public function test_public_portal_action_is_attributed_to_the_client_even_when_staff_is_also_logged_in(): void
    {
        $staff = User::factory()->create(['name' => 'Sandrele Reis']);
        $client = Client::factory()->create(['name' => 'Cliente do Portal', 'document' => '123.456.789-09']);
        $contract = Contract::factory()->create(['client_id' => $client->id]);
        $task = ContractTask::factory()->create(['contract_id' => $contract->id, 'status' => ContractTask::STATUS_A_INICIAR]);

        // Staff stays logged in (e.g. previewing the client's own link in the
        // same browser) while the request itself hits the public portal.
        $this->actingAs($staff);
        $this->post(route('public.verify', $contract->public_token), ['document' => $client->document]);

        $this->patch(route('public.tasks.status', ['token' => $contract->public_token, 'task' => $task]), [
            'status' => ContractTask::STATUS_CONCLUIDA,
        ])->assertOk();

        $log = AuditLog::where('auditable_type', ContractTask::class)
            ->where('auditable_id', $task->id)
            ->where('action', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('client', $log->actor_type);
        $this->assertSame('Cliente do Portal', $log->actor_name);
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

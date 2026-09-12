<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogRevertTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revert_an_updated_field(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::factory()->create(['name' => 'Nome Original']);
        $client->update(['name' => 'Nome Alterado']);

        $log = AuditLog::where('auditable_type', Client::class)
            ->where('auditable_id', $client->id)
            ->where('action', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertTrue($log->isRevertible());

        $this->post(route('audit-logs.revert', $log))->assertRedirect();

        $this->assertSame('Nome Original', $client->fresh()->name);

        $revertLog = AuditLog::where('auditable_id', $client->id)->latest('id')->first();
        $this->assertSame('reverted', $revertLog->action);
    }

    public function test_revert_redirects_to_the_contracts_activity_tab_when_scoped_to_a_contract(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::factory()->create();
        $contract = Contract::factory()->create(['client_id' => $client->id, 'notes' => 'Original']);
        $contract->update(['notes' => 'Alterado']);

        $log = AuditLog::where('auditable_type', Contract::class)
            ->where('auditable_id', $contract->id)
            ->where('action', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->post(route('audit-logs.revert', $log))
            ->assertRedirect(route('contracts.show', ['contract' => $contract, 'tab' => 'atividades']));

        $this->assertSame('Original', $contract->fresh()->notes);
    }

    public function test_created_action_cannot_be_reverted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $client = Client::factory()->create();

        $log = AuditLog::where('auditable_type', Client::class)
            ->where('auditable_id', $client->id)
            ->where('action', 'created')
            ->firstOrFail();

        $this->assertFalse($log->isRevertible());
        $this->post(route('audit-logs.revert', $log))->assertStatus(422);
    }

    public function test_guest_cannot_revert(): void
    {
        $client = Client::factory()->create(['name' => 'A']);
        $client->update(['name' => 'B']);

        $log = AuditLog::where('auditable_type', Client::class)->where('action', 'updated')->firstOrFail();

        $this->post(route('audit-logs.revert', $log))->assertRedirect(route('login'));
        $this->assertSame('B', $client->fresh()->name);
    }
}

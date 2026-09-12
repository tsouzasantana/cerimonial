<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_can_be_updated_via_the_inline_ajax_endpoint(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $task = ContractTask::factory()->create(['contract_id' => $contract->id, 'status' => ContractTask::STATUS_A_INICIAR]);

        $response = $this->actingAs($user)->patchJson(
            route('contract-tasks.status', [$contract, $task]),
            ['status' => ContractTask::STATUS_CONCLUIDA]
        );

        $response->assertOk()->assertJson(['status' => 'concluida', 'is_overdue' => false]);
        $this->assertSame(ContractTask::STATUS_CONCLUIDA, $task->fresh()->status);
    }

    public function test_a_task_past_due_and_still_open_is_reported_as_overdue(): void
    {
        $task = ContractTask::factory()->create([
            'due_date' => now()->subDay(),
            'status' => ContractTask::STATUS_EM_ANDAMENTO,
        ]);

        $this->assertTrue($task->isOverdue());
    }

    public function test_a_completed_task_past_due_is_not_overdue(): void
    {
        $task = ContractTask::factory()->create([
            'due_date' => now()->subDay(),
            'status' => ContractTask::STATUS_CONCLUIDA,
        ]);

        $this->assertFalse($task->isOverdue());
    }

    public function test_admin_can_add_an_ad_hoc_task_to_a_contract(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();

        $this->actingAs($user)->post(route('contract-tasks.store', $contract), [
            'name' => 'Confirmar decoração extra',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ])->assertRedirect();

        $this->assertDatabaseHas('contract_tasks', [
            'contract_id' => $contract->id,
            'name' => 'Confirmar decoração extra',
        ]);
    }

    public function test_admin_can_edit_task_name_date_and_notes(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $task = ContractTask::factory()->create(['contract_id' => $contract->id]);

        $this->actingAs($user)->put(route('contract-tasks.update', [$contract, $task]), [
            'name' => 'Nome atualizado',
            'due_date' => '2027-05-01',
            'status' => ContractTask::STATUS_EM_ANDAMENTO,
            'notes' => 'Observação de teste',
        ])->assertRedirect();

        $task->refresh();
        $this->assertSame('Nome atualizado', $task->name);
        $this->assertSame('2027-05-01', $task->due_date->format('Y-m-d'));
        $this->assertSame('Observação de teste', $task->notes);
    }

    public function test_task_can_be_inactivated_and_restored(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create();
        $task = ContractTask::factory()->create(['contract_id' => $contract->id]);

        $this->actingAs($user)->delete(route('contract-tasks.destroy', [$contract, $task]))
            ->assertRedirect();
        $this->assertSoftDeleted($task);

        $this->actingAs($user)->post(route('contract-tasks.restore', [$contract, $task->id]))
            ->assertRedirect();
        $this->assertNotSoftDeleted($task);
    }
}

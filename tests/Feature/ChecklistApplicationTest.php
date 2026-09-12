<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplate;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_contract_applies_the_checklist_template_with_correct_due_dates(): void
    {
        ChecklistTemplate::create(['name' => 'Reunião inicial', 'days_offset' => 90]);
        ChecklistTemplate::create(['name' => 'Dia do evento', 'days_offset' => 0]);
        ChecklistTemplate::create(['name' => 'Agradecimento', 'days_offset' => -7]);

        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)->post(route('contracts.store'), [
            'client_id' => $client->id,
            'event_date' => '2027-01-02',
            'status' => Contract::STATUS_RASCUNHO,
            'discount' => 0,
        ])->assertRedirect();

        $contract = Contract::firstOrFail();
        $this->assertCount(3, $contract->tasks);

        $this->assertSame('2026-10-04', $contract->tasks->firstWhere('name', 'Reunião inicial')->due_date->format('Y-m-d'));
        $this->assertSame('2027-01-02', $contract->tasks->firstWhere('name', 'Dia do evento')->due_date->format('Y-m-d'));
        $this->assertSame('2027-01-09', $contract->tasks->firstWhere('name', 'Agradecimento')->due_date->format('Y-m-d'));
        $this->assertTrue($contract->tasks->every(fn ($task) => $task->status === ContractTask::STATUS_A_INICIAR));
    }

    public function test_new_contract_receives_a_unique_public_token(): void
    {
        $client = Client::factory()->create();

        $contractA = Contract::factory()->create(['client_id' => $client->id]);
        $contractB = Contract::factory()->create(['client_id' => $client->id]);

        $this->assertNotEmpty($contractA->public_token);
        $this->assertNotEmpty($contractB->public_token);
        $this->assertNotSame($contractA->public_token, $contractB->public_token);
    }

    public function test_updating_event_date_without_shift_flag_keeps_original_checklist_dates(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['event_date' => '2027-01-02']);
        $task = ContractTask::factory()->create(['contract_id' => $contract->id, 'due_date' => '2027-01-01']);

        $this->actingAs($user)->put(route('contracts.update', $contract), [
            'client_id' => $contract->client_id,
            'event_date' => '2027-01-10',
            'status' => Contract::STATUS_RASCUNHO,
            'discount' => 0,
        ])->assertRedirect();

        $this->assertSame('2027-01-01', $task->fresh()->due_date->format('Y-m-d'));
    }

    public function test_updating_event_date_with_shift_flag_moves_all_checklist_dates_by_the_same_delta(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['event_date' => '2027-01-02']);
        $taskA = ContractTask::factory()->create(['contract_id' => $contract->id, 'due_date' => '2027-01-01']);
        $taskB = ContractTask::factory()->create(['contract_id' => $contract->id, 'due_date' => '2026-10-04']);

        $this->actingAs($user)->put(route('contracts.update', $contract), [
            'client_id' => $contract->client_id,
            'event_date' => '2027-01-10',
            'status' => Contract::STATUS_RASCUNHO,
            'discount' => 0,
            'shift_checklist_dates' => 1,
        ])->assertRedirect();

        $this->assertSame('2027-01-09', $taskA->fresh()->due_date->format('Y-m-d'));
        $this->assertSame('2026-10-12', $taskB->fresh()->due_date->format('Y-m-d'));
    }
}

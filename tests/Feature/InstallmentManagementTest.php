<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function contract(): Contract
    {
        $client = Client::factory()->create();

        return Contract::factory()->create(['client_id' => $client->id]);
    }

    public function test_admin_can_generate_installments_in_batch(): void
    {
        $user = User::factory()->create();
        $contract = $this->contract();

        $this->actingAs($user)->post(route('installments.store-batch', $contract), [
            'total_amount' => '1000.00',
            'installment_count' => 3,
            'first_due_date' => '2027-01-10',
            'interval_months' => 1,
        ])->assertRedirect(route('contracts.show', $contract));

        $installments = $contract->installments()->orderBy('number')->get();

        $this->assertCount(3, $installments);
        $this->assertEquals('333.33', $installments[0]->amount);
        $this->assertEquals('333.33', $installments[1]->amount);
        $this->assertEquals('333.34', $installments[2]->amount);
        $this->assertEquals('1000.00', number_format((float) $installments->sum('amount'), 2, '.', ''));

        $this->assertSame([1, 2, 3], $installments->pluck('number')->all());
        $this->assertSame('2027-01-10', $installments[0]->due_date->format('Y-m-d'));
        $this->assertSame('2027-02-10', $installments[1]->due_date->format('Y-m-d'));
        $this->assertSame('2027-03-10', $installments[2]->due_date->format('Y-m-d'));

        $installments->each(fn (Installment $installment) => $this->assertSame(Installment::STATUS_PENDENTE, $installment->status));
    }

    public function test_batch_generation_continues_numbering_after_existing_installments(): void
    {
        $user = User::factory()->create();
        $contract = $this->contract();
        Installment::factory()->create(['contract_id' => $contract->id, 'number' => 1]);
        Installment::factory()->create(['contract_id' => $contract->id, 'number' => 2]);

        $this->actingAs($user)->post(route('installments.store-batch', $contract), [
            'total_amount' => '200.00',
            'installment_count' => 2,
            'first_due_date' => '2027-05-01',
            'interval_months' => 1,
        ])->assertRedirect();

        $numbers = $contract->installments()->orderBy('number')->pluck('number')->all();
        $this->assertSame([1, 2, 3, 4], $numbers);
    }

    public function test_batch_generation_requires_valid_input(): void
    {
        $user = User::factory()->create();
        $contract = $this->contract();

        $this->actingAs($user)->post(route('installments.store-batch', $contract), [
            'total_amount' => '0',
            'installment_count' => 0,
            'first_due_date' => 'not-a-date',
            'interval_months' => 0,
        ])->assertSessionHasErrors(['total_amount', 'installment_count', 'first_due_date', 'interval_months']);

        $this->assertSame(0, $contract->installments()->count());
    }

    public function test_guest_cannot_generate_installments(): void
    {
        $contract = $this->contract();

        $this->post(route('installments.store-batch', $contract), [
            'total_amount' => '100',
            'installment_count' => 1,
            'first_due_date' => '2027-01-01',
            'interval_months' => 1,
        ])->assertRedirect(route('login'));
    }
}

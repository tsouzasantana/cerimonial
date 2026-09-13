<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The price/amount/discount fields switched from a plain <input type=number>
 * to the x-currency-input component (a masked display field paired with a
 * hidden field carrying the real value). These tests confirm the submitted
 * field name and value format are unchanged from the caller's perspective.
 */
class CurrencyInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_price_submitted_as_plain_decimal_is_stored(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/services', [
            'name' => 'Serviço com máscara',
            'price' => '1500.00',
            'active' => '1',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('services', ['name' => 'Serviço com máscara', 'price' => 1500]);
    }

    public function test_contract_discount_submitted_as_plain_decimal_is_stored(): void
    {
        $user = User::factory()->create();
        $contract = Contract::factory()->create(['discount' => 0]);

        $this->actingAs($user)->patch(route('contracts.update-discount', $contract), [
            'discount_type' => 'fixed',
            'discount_value_fixed' => '250.50',
            'discount_value_percentage' => '0',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('250.50', $contract->fresh()->discount);
    }
}

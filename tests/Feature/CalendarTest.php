<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
    }

    public function test_calendar_shows_events_for_the_requested_month(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Cliente do Mês']);
        $contract = Contract::factory()->create(['client_id' => $client->id, 'event_date' => '2027-11-20']);

        Client::factory()
            ->has(Contract::factory()->state(['event_date' => '2027-12-05']), 'contracts')
            ->create(['name' => 'Cliente de outro mês']);

        $response = $this->actingAs($user)->get(route('calendar.index', ['month' => '2027-11']));

        $response->assertOk();
        $response->assertSee('Cliente do Mês');
        $response->assertDontSee('Cliente de outro mês');
        $response->assertSee(route('contracts.show', $contract), false);
    }

    public function test_invalid_month_parameter_falls_back_to_current_month(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('calendar.index', ['month' => 'not-a-month']));

        $response->assertOk();
        $response->assertSee(now()->translatedFormat('F \d\e Y'));
    }
}

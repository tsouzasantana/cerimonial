<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('search.index'))->assertRedirect(route('login'));
    }

    public function test_empty_query_shows_a_prompt_without_erroring(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.index'));

        $response->assertOk();
        $response->assertSee('Digite um termo para buscar');
    }

    public function test_finds_client_by_name_and_document(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Maria da Silva', 'document' => '111.222.333-44']);
        Client::factory()->create(['name' => 'Outro Cliente']);

        $response = $this->actingAs($user)->get(route('search.index', ['q' => 'Maria']));
        $response->assertOk()->assertSee('Maria da Silva')->assertDontSee('Outro Cliente');

        $response = $this->actingAs($user)->get(route('search.index', ['q' => '111.222.333-44']));
        $response->assertOk()->assertSee('Maria da Silva');
    }

    public function test_finds_contract_by_client_name_and_id(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Cliente do Contrato']);
        $contract = Contract::factory()->create(['client_id' => $client->id]);
        Contract::factory()->create(['client_id' => Client::factory()->create(['name' => 'Outro'])->id]);

        $response = $this->actingAs($user)->get(route('search.index', ['q' => 'Cliente do Contrato']));
        $response->assertOk()->assertSee("#{$contract->id}");

        $response = $this->actingAs($user)->get(route('search.index', ['q' => (string) $contract->id]));
        $response->assertOk()->assertSee('Cliente do Contrato');
    }

    public function test_finds_vendor_by_name(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $contract = Contract::factory()->create(['client_id' => $client->id]);
        $type = VendorServiceType::factory()->create();
        Vendor::factory()->create(['contract_id' => $contract->id, 'vendor_service_type_id' => $type->id, 'name' => 'Floricultura Bela Flor']);

        $response = $this->actingAs($user)->get(route('search.index', ['q' => 'Bela Flor']));

        $response->assertOk();
        $response->assertSee('Floricultura Bela Flor');
    }

    public function test_shows_no_results_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.index', ['q' => 'termo-que-nao-existe-em-nada']));

        $response->assertOk();
        $response->assertSee('Nenhum resultado encontrado');
    }

    public function test_inactivated_client_is_not_found(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Cliente Inativado']);
        $client->delete();

        $response = $this->actingAs($user)->get(route('search.index', ['q' => 'Cliente Inativado']));

        $response->assertOk();
        $response->assertSee('Nenhum resultado encontrado');
    }
}

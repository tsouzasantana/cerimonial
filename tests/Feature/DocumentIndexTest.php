<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentFile;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_linked_to_a_contract_shows_client_name_and_event_date_with_a_link(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Fernanda Lima']);
        $contract = Contract::factory()->create(['client_id' => $client->id, 'event_date' => '2027-06-20']);
        DocumentFile::factory()->create(['contract_id' => $contract->id, 'title' => 'Contrato assinado']);

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertOk();
        $response->assertSee('Fernanda Lima');
        $response->assertSee('20/06/2027');
        $response->assertSee(route('contracts.show', $contract), false);
    }

    public function test_document_linked_to_a_vendor_also_shows_the_vendor_name(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Renata Alves']);
        $contract = Contract::factory()->create(['client_id' => $client->id]);
        $vendor = Vendor::factory()->create(['contract_id' => $contract->id, 'name' => 'Buffet Sabor']);
        DocumentFile::factory()->create(['contract_id' => $contract->id, 'vendor_id' => $vendor->id]);

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertOk();
        $response->assertSee('Renata Alves');
        $response->assertSee('Fornecedor: Buffet Sabor');
    }

    public function test_search_matches_the_linked_contracts_client_name(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Juliana Prado']);
        $contract = Contract::factory()->create(['client_id' => $client->id]);
        DocumentFile::factory()->create(['contract_id' => $contract->id, 'title' => 'Documento genérico']);
        DocumentFile::factory()->create(['title' => 'Outro documento sem contrato']);

        $response = $this->actingAs($user)->get(route('documents.index', ['search' => 'Juliana']));

        $response->assertOk();
        $response->assertSee('Documento genérico');
        $response->assertDontSee('Outro documento sem contrato');
    }
}

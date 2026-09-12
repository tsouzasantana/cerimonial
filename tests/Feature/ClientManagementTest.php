<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/clients')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_a_client(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/clients', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => '529.982.247-25',
        ]);

        $client = Client::first();
        $response->assertRedirect(route('clients.show', $client));
        $this->assertSame('Maria Silva', $client->name);
    }

    public function test_client_can_be_inactivated_and_restored(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this->actingAs($user)->delete(route('clients.destroy', $client))
            ->assertRedirect(route('clients.index'));

        $this->assertSoftDeleted($client);

        $this->actingAs($user)->post(route('clients.restore', $client->id))
            ->assertRedirect(route('clients.index'));

        $this->assertNotSoftDeleted($client);
    }
}

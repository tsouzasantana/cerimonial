<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFieldValidationTest extends TestCase
{
    use RefreshDatabase;

    private function baseFields(): array
    {
        return ['name' => 'Cliente Teste'];
    }

    public function test_phone_with_invalid_ddd_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'phone' => '(00) 98765-4321',
        ])->assertSessionHasErrors('phone');
    }

    public function test_mobile_phone_missing_leading_nine_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'phone' => '(11) 12345-6789',
        ])->assertSessionHasErrors('phone');
    }

    public function test_valid_phone_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'phone' => '(11) 98765-4321',
        ])->assertSessionDoesntHaveErrors('phone');

        $this->assertDatabaseHas('clients', ['phone' => '(11) 98765-4321']);
    }

    public function test_phone_is_optional(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields())
            ->assertSessionDoesntHaveErrors('phone');
    }

    public function test_future_birth_date_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'birth_date' => now()->addDay()->format('Y-m-d'),
        ])->assertSessionHasErrors('birth_date');
    }

    public function test_implausibly_old_birth_date_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'birth_date' => '1850-01-01',
        ])->assertSessionHasErrors('birth_date');
    }

    public function test_valid_birth_date_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'birth_date' => '1990-05-15',
        ])->assertSessionDoesntHaveErrors('birth_date');
    }

    public function test_invalid_state_abbreviation_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'address_state' => 'ZZ',
        ])->assertSessionHasErrors('address_state');
    }

    public function test_valid_state_abbreviation_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'address_state' => 'SP',
        ])->assertSessionDoesntHaveErrors('address_state');
    }

    public function test_malformed_zipcode_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'address_zipcode' => '123',
        ])->assertSessionHasErrors('address_zipcode');
    }

    public function test_valid_zipcode_formatted_or_raw_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clients', $this->baseFields() + [
            'address_zipcode' => '20040-020',
        ])->assertSessionDoesntHaveErrors('address_zipcode');

        $this->actingAs($user)->post('/clients', ['name' => 'Outro Cliente'] + [
            'address_zipcode' => '20040020',
        ])->assertSessionDoesntHaveErrors('address_zipcode');
    }
}

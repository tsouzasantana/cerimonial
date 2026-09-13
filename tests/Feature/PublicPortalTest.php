<?php

namespace Tests\Feature;

use App\Mail\ClientActivityMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\DocumentFile;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    private function contractWithClientCpf(string $cpf = '123.456.789-09'): Contract
    {
        $client = Client::factory()->create(['document' => $cpf]);

        return Contract::factory()->create(['client_id' => $client->id]);
    }

    public function test_gate_page_loads_for_a_valid_token(): void
    {
        $contract = $this->contractWithClientCpf();

        $this->get(route('public.gate', $contract->public_token))->assertOk();
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->get(route('public.gate', 'token-que-nao-existe'))
            ->assertNotFound()
            ->assertSee('Este link não é mais válido');
    }

    public function test_unknown_token_on_protected_route_returns_friendly_not_found(): void
    {
        $this->get(route('public.show', 'token-que-nao-existe'))
            ->assertNotFound()
            ->assertSee('Este link não é mais válido');
    }

    public function test_contract_page_redirects_to_gate_when_not_verified(): void
    {
        $contract = $this->contractWithClientCpf();

        $this->get(route('public.show', $contract->public_token))
            ->assertRedirect(route('public.gate', $contract->public_token));
    }

    public function test_wrong_cpf_is_rejected(): void
    {
        $contract = $this->contractWithClientCpf('123.456.789-09');

        $this->from(route('public.gate', $contract->public_token))
            ->post(route('public.verify', $contract->public_token), ['document' => '999.999.999-99'])
            ->assertSessionHasErrors('document');

        $this->get(route('public.show', $contract->public_token))
            ->assertRedirect(route('public.gate', $contract->public_token));
    }

    public function test_verify_is_rate_limited_after_repeated_wrong_attempts(): void
    {
        $contract = $this->contractWithClientCpf('123.456.789-09');

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('public.verify', $contract->public_token), ['document' => '999.999.999-99']);
        }

        $this->post(route('public.verify', $contract->public_token), ['document' => '123.456.789-09'])
            ->assertRedirect(route('public.gate', $contract->public_token))
            ->assertSessionHasErrors('document');

        $this->get(route('public.show', $contract->public_token))
            ->assertRedirect(route('public.gate', $contract->public_token));
    }

    public function test_correct_cpf_grants_access_regardless_of_formatting(): void
    {
        $contract = $this->contractWithClientCpf('123.456.789-09');

        $this->post(route('public.verify', $contract->public_token), ['document' => '12345678909'])
            ->assertRedirect(route('public.show', $contract->public_token));

        $this->get(route('public.show', $contract->public_token))
            ->assertOk()
            ->assertSee($contract->client->name);
    }

    public function test_verified_client_can_update_task_status_via_ajax(): void
    {
        $contract = $this->contractWithClientCpf();
        $task = ContractTask::factory()->create(['contract_id' => $contract->id, 'status' => ContractTask::STATUS_A_INICIAR]);

        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->patchJson(route('public.tasks.status', [$contract->public_token, $task]), [
            'status' => ContractTask::STATUS_EM_ANDAMENTO,
        ])->assertOk()->assertJson(['status' => 'em_andamento']);

        $this->assertSame(ContractTask::STATUS_EM_ANDAMENTO, $task->fresh()->status);
    }

    public function test_verified_client_can_edit_task_date_and_notes_but_not_name(): void
    {
        $contract = $this->contractWithClientCpf();
        $task = ContractTask::factory()->create(['contract_id' => $contract->id, 'name' => 'Nome original']);

        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->put(route('public.tasks.update', [$contract->public_token, $task]), [
            'name' => 'Tentando trocar o nome',
            'due_date' => '2027-06-01',
            'status' => ContractTask::STATUS_EM_ANDAMENTO,
            'notes' => 'Atualizado pelo cliente',
        ])->assertRedirect();

        $task->refresh();
        $this->assertSame('Nome original', $task->name);
        $this->assertSame('2027-06-01', $task->due_date->format('Y-m-d'));
        $this->assertSame('Atualizado pelo cliente', $task->notes);
    }

    public function test_verified_client_can_add_and_inactivate_a_checklist_task(): void
    {
        $contract = $this->contractWithClientCpf();
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->post(route('public.tasks.store', $contract->public_token), [
            'name' => 'Tarefa criada pelo cliente',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ])->assertRedirect();

        $task = ContractTask::where('contract_id', $contract->id)->firstOrFail();
        $this->assertSame('Tarefa criada pelo cliente', $task->name);

        $this->delete(route('public.tasks.destroy', [$contract->public_token, $task]))
            ->assertRedirect();

        $this->assertSoftDeleted($task);
    }

    public function test_verified_client_can_upload_a_document_as_a_guest(): void
    {
        Storage::fake('local');
        $contract = $this->contractWithClientCpf();
        $type = DocumentType::factory()->create();

        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->post(route('public.documents.store', $contract->public_token), [
            'title' => 'RG enviado pelo cliente',
            'document_type_id' => $type->id,
            'file' => UploadedFile::fake()->create('rg.pdf', 5),
        ])->assertRedirect();

        $document = DocumentFile::firstOrFail();
        $this->assertSame($contract->id, $document->contract_id);
        $this->assertNull($document->uploaded_by);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_document_upload_requires_file_or_url(): void
    {
        $contract = $this->contractWithClientCpf();
        $type = DocumentType::factory()->create();

        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->post(route('public.documents.store', $contract->public_token), [
            'title' => 'Sem anexo',
            'document_type_id' => $type->id,
        ])->assertSessionHasErrors(['file', 'url']);
    }

    public function test_verification_on_one_contract_does_not_grant_access_to_another(): void
    {
        $contractA = $this->contractWithClientCpf('111.111.111-11');
        $contractB = $this->contractWithClientCpf('222.222.222-22');

        $this->post(route('public.verify', $contractA->public_token), ['document' => '111.111.111-11']);

        $this->get(route('public.show', $contractB->public_token))
            ->assertRedirect(route('public.gate', $contractB->public_token));
    }

    public function test_regenerating_the_public_link_invalidates_the_old_token(): void
    {
        $user = User::factory()->create();
        $contract = $this->contractWithClientCpf();
        $oldToken = $contract->public_token;

        $this->actingAs($user)->post(route('contracts.regenerate-public-link', $contract))
            ->assertRedirect();

        $this->get(route('public.gate', $oldToken))->assertNotFound();
        $this->get(route('public.gate', $contract->fresh()->public_token))->assertOk();
    }

    public function test_public_session_cannot_access_authenticated_admin_routes(): void
    {
        $contract = $this->contractWithClientCpf();
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->get(route('contracts.index'))->assertRedirect(route('login'));
    }

    public function test_verified_client_can_create_a_vendor(): void
    {
        $contract = $this->contractWithClientCpf();
        $type = VendorServiceType::factory()->create();
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->post(route('public.vendors.store', $contract->public_token), [
            'name' => 'Floricultura Jardim',
            'vendor_service_type_id' => $type->id,
            'status' => Vendor::STATUS_A_PRESTAR,
            'payment_status' => Vendor::PAYMENT_NAO_PAGO,
        ])->assertRedirect();

        $this->assertDatabaseHas('vendors', [
            'contract_id' => $contract->id,
            'name' => 'Floricultura Jardim',
        ]);
    }

    public function test_verified_client_can_update_vendor_status_but_not_rename_it(): void
    {
        $contract = $this->contractWithClientCpf();
        $vendor = Vendor::factory()->create(['contract_id' => $contract->id, 'name' => 'Nome original']);
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->put(route('public.vendors.update', [$contract->public_token, $vendor]), [
            'name' => 'Tentando trocar o nome',
            'status' => Vendor::STATUS_PRESTADO,
            'notes' => 'Atualizado pelo cliente',
        ])->assertRedirect();

        $vendor->refresh();
        $this->assertSame('Nome original', $vendor->name);
        $this->assertSame(Vendor::STATUS_PRESTADO, $vendor->status);
    }

    public function test_verified_client_can_inactivate_a_vendor(): void
    {
        $contract = $this->contractWithClientCpf();
        $vendor = Vendor::factory()->create(['contract_id' => $contract->id]);
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->delete(route('public.vendors.destroy', [$contract->public_token, $vendor]))
            ->assertRedirect();

        $this->assertSoftDeleted($vendor);
    }

    public function test_staff_is_notified_by_email_when_client_changes_something(): void
    {
        Mail::fake();
        $staff = User::factory()->create();
        $contract = $this->contractWithClientCpf();
        $this->post(route('public.verify', $contract->public_token), ['document' => $contract->client->document]);

        $this->post(route('public.tasks.store', $contract->public_token), [
            'name' => 'Tarefa criada pelo cliente',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ])->assertRedirect();

        Mail::assertQueued(ClientActivityMail::class, function (ClientActivityMail $mail) use ($contract, $staff) {
            return $mail->log->contract_id === $contract->id
                && $mail->hasTo($staff->email);
        });
    }

    public function test_admin_actions_do_not_trigger_client_activity_notification(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $contract = $this->contractWithClientCpf();

        $this->actingAs($user)->post(route('contract-tasks.store', $contract), [
            'name' => 'Tarefa criada pelo admin',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ])->assertRedirect();

        Mail::assertNotQueued(ClientActivityMail::class);
    }

    public function test_client_cannot_attach_a_document_to_a_vendor_from_another_contract(): void
    {
        $contractA = $this->contractWithClientCpf('111.111.111-11');
        $contractB = $this->contractWithClientCpf('222.222.222-22');
        $vendorOnA = Vendor::factory()->create(['contract_id' => $contractA->id]);
        $type = DocumentType::factory()->create();

        $this->post(route('public.verify', $contractB->public_token), ['document' => '222.222.222-22']);

        $this->post(route('public.documents.store', $contractB->public_token), [
            'title' => 'Ataque',
            'document_type_id' => $type->id,
            'vendor_id' => $vendorOnA->id,
            'url' => 'https://example.com/doc',
        ])->assertNotFound();
    }
}

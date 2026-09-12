<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentInactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_document_stores_it_on_local_disk(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $type = DocumentType::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'RG do cliente',
            'document_type_id' => $type->id,
            'file' => UploadedFile::fake()->create('rg.pdf', 10),
        ]);

        $response->assertRedirect();
        $document = DocumentFile::first();
        Storage::disk('local')->assertExists($document->path);
        $this->assertFalse($document->isLink());
    }

    public function test_uploading_a_document_as_a_cloud_link_does_not_require_a_file(): void
    {
        $user = User::factory()->create();
        $type = DocumentType::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Moodboard da decoração',
            'document_type_id' => $type->id,
            'url' => 'https://drive.google.com/some-folder',
        ]);

        $response->assertRedirect();
        $document = DocumentFile::first();
        $this->assertTrue($document->isLink());
        $this->assertNull($document->path);
    }

    public function test_document_requires_either_a_file_or_a_url(): void
    {
        $user = User::factory()->create();
        $type = DocumentType::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title' => 'Documento sem anexo',
            'document_type_id' => $type->id,
        ]);

        $response->assertSessionHasErrors(['file', 'url']);
        $this->assertSame(0, DocumentFile::count());
    }

    public function test_deleting_a_document_soft_deletes_it_instead_of_removing_the_file(): void
    {
        Storage::fake('local');
        $path = 'documents/sample.pdf';
        Storage::disk('local')->put($path, 'conteudo');

        $user = User::factory()->create();
        $document = DocumentFile::factory()->create(['path' => $path]);

        $this->actingAs($user)->delete(route('documents.destroy', $document))
            ->assertRedirect();

        $this->assertSoftDeleted($document);
        Storage::disk('local')->assertExists($path);

        $this->assertDatabaseMissing('document_files', [
            'id' => $document->id,
            'deleted_at' => null,
        ]);
    }

    public function test_an_inactivated_document_can_be_restored(): void
    {
        $user = User::factory()->create();
        $document = DocumentFile::factory()->create();
        $document->delete();

        $this->actingAs($user)->post(route('documents.restore', $document->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted($document);
    }

    public function test_inactivated_documents_are_hidden_from_default_listing(): void
    {
        $user = User::factory()->create();
        $active = DocumentFile::factory()->create(['title' => 'Documento ativo']);
        $inactive = DocumentFile::factory()->create(['title' => 'Documento inativo']);
        $inactive->delete();

        $response = $this->actingAs($user)->get(route('documents.index'));

        $response->assertSee('Documento ativo');
        $response->assertDontSee('Documento inativo');

        $trashedResponse = $this->actingAs($user)->get(route('documents.index', ['trashed' => 1]));
        $trashedResponse->assertSee('Documento inativo');
    }
}

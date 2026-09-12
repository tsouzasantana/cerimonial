<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_document_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('document-types.store'), [
            'name' => 'Inspiração / referência visual',
        ])->assertRedirect(route('document-types.index'));

        $this->assertDatabaseHas('document_types', ['name' => 'Inspiração / referência visual']);
    }

    public function test_a_document_type_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $type = DocumentType::factory()->create();
        DocumentFile::factory()->create(['document_type_id' => $type->id]);

        $this->actingAs($user)->delete(route('document-types.destroy', $type))
            ->assertRedirect(route('document-types.index'));

        $this->assertDatabaseHas('document_types', ['id' => $type->id]);
    }

    public function test_an_unused_document_type_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $type = DocumentType::factory()->create();

        $this->actingAs($user)->delete(route('document-types.destroy', $type))
            ->assertRedirect(route('document-types.index'));

        $this->assertDatabaseMissing('document_types', ['id' => $type->id]);
    }
}

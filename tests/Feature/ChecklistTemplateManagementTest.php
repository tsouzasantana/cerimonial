<?php

namespace Tests\Feature;

use App\Models\ChecklistTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_checklist_template(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('checklist-templates.store'), [
            'name' => 'Confirmar cerimonialista',
            'days_offset' => 15,
        ])->assertRedirect(route('checklist-templates.index'));

        $this->assertDatabaseHas('checklist_templates', [
            'name' => 'Confirmar cerimonialista',
            'days_offset' => 15,
        ]);
    }

    public function test_days_offset_accepts_negative_values_for_post_event_tasks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('checklist-templates.store'), [
            'name' => 'Enviar fotos do evento',
            'days_offset' => -10,
        ])->assertRedirect();

        $this->assertDatabaseHas('checklist_templates', ['days_offset' => -10]);
    }

    public function test_updating_a_template_does_not_affect_existing_contracts(): void
    {
        $user = User::factory()->create();
        $template = ChecklistTemplate::factory()->create(['days_offset' => 10]);

        $this->actingAs($user)->put(route('checklist-templates.update', $template), [
            'name' => $template->name,
            'days_offset' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('checklist_templates', ['id' => $template->id, 'days_offset' => 20]);
    }
}

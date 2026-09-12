<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::factory(),
            'title' => fake()->sentence(3),
            'original_filename' => 'documento.pdf',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }

    public function asLink(): static
    {
        return $this->state(fn () => [
            'original_filename' => null,
            'path' => null,
            'mime_type' => null,
            'size' => 0,
            'url' => fake()->url(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * File metadata only; tests that read the file store it themselves.
 *
 * @extends Factory<DocumentFile>
 */
class DocumentFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'version' => 1,
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'original_name' => 'scan.pdf',
            'mime' => 'application/pdf',
            'size' => 120_000,
        ];
    }
}

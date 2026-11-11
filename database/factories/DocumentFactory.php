<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A typed document owned by a candidate. Add files with DocumentFile::factory().
 *
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documentable_type' => (new Candidate)->getMorphClass(),
            'documentable_id' => Candidate::factory(),
            'document_type_id' => DocumentType::factory(),
            'title' => null,
            'current_version' => 1,
            'uploaded_at' => now(),
        ];
    }

    public function extra(string $title = 'Extra document'): static
    {
        return $this->state(['document_type_id' => null, 'title' => $title]);
    }
}

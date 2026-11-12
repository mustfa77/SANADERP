<?php

namespace Database\Factories;

use App\Models\Authority;
use App\Models\Candidate;
use App\Models\DocumentPrint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentPrint>
 */
class DocumentPrintFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documentable_type' => (new Candidate)->getMorphClass(),
            'documentable_id' => Candidate::factory(),
            'authority_id' => Authority::factory(),
            'file_path' => 'document-prints/'.fake()->uuid().'.pdf',
            'printed_at' => now(),
        ];
    }
}

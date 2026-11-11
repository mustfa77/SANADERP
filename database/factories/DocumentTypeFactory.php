<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Global (all countries / professions) by default.
 *
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => null,
            'profession_id' => null,
            'authority_id' => null,
            'name_ar' => 'مستند '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => 'Document '.fake()->unique()->numberBetween(1, 9999),
            'is_required' => false,
            'include_in_authority_file' => false,
            'is_active' => true,
        ];
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function inAuthorityFile(): static
    {
        return $this->state(['include_in_authority_file' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

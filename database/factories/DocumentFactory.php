<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'agency_id' => fn (array $attributes) => Candidate::find($attributes['candidate_id'])?->agency_id,
            'type' => fake()->randomElement(['passport', 'medical', 'contract', 'visa']),
            'file_path' => null,
        ];
    }
}

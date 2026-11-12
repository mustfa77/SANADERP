<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name_ar' => fake('ar_SA')->name('female'),
            'name_en' => fake()->name('female'),
            'nationality' => fake()->randomElement(['PH', 'KE', 'UG', 'ET', 'LK']),
            'passport_number' => fake()->bothify('P#######??'),
            'status' => 'new',
        ];
    }
}

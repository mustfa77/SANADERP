<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name_ar' => fake('ar_SA')->name('female'),
            'name_en' => fake()->name('female'),
            'nationality' => fake()->randomElement(['PH', 'KE', 'UG', 'ET', 'LK']),
            'passport_number' => fake()->unique()->bothify('P#######??'),
            'iqama_number' => '2'.fake()->unique()->numerify('#########'),
        ];
    }
}

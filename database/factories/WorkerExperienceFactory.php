<?php

namespace Database\Factories;

use App\Models\Worker;
use App\Models\WorkerExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerExperience>
 */
class WorkerExperienceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_id' => Worker::factory(),
            'country' => fake()->randomElement(['Saudi Arabia', 'Kuwait', 'Qatar', 'UAE', 'Jordan']),
            'position' => fake()->randomElement(['Housemaid', 'Nanny', 'Driver']),
            'duration' => fake()->numberBetween(1, 6).' years',
        ];
    }
}

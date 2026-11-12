<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
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
            'driver_id' => User::factory()->driver(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
            'status' => 'scheduled',
        ];
    }
}

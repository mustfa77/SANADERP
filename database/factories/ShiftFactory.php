<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => 'وردية '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => 'Shift '.fake()->unique()->numberBetween(1, 9999),
            'start_time' => '08:00:00',
            'move_time' => '07:15:00',
            'hours' => 4,
            'is_active' => true,
        ];
    }
}

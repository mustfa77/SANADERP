<?php

namespace Database\Factories;

use App\Enums\CampType;
use App\Models\Branch;
use App\Models\Camp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Camp>
 */
class CampFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name_ar' => 'سكن '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => 'Camp '.fake()->unique()->numberBetween(1, 9999),
            'district_ar' => 'حي النرجس',
            'district_en' => 'Al Narjis',
            'capacity' => fake()->numberBetween(10, 80),
            'type' => fake()->randomElement(CampType::cases()),
            'is_active' => true,
        ];
    }
}

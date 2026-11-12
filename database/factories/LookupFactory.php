<?php

namespace Database\Factories;

use App\Enums\LookupType;
use App\Models\Lookup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lookup>
 */
class LookupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(LookupType::cases()),
            'name_ar' => 'خيار '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => 'Option '.fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ];
    }
}

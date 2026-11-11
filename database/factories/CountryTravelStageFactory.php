<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\CountryTravelStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CountryTravelStage>
 */
class CountryTravelStageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name_ar' => 'مرحلة '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => 'Stage '.fake()->unique()->numberBetween(1, 9999),
            'max_days' => fake()->numberBetween(3, 30),
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Authority;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Authority>
 */
class AuthorityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name_ar' => 'مكتب العمل '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => 'Labor Office '.fake()->unique()->numberBetween(1, 9999),
            'city_ar' => 'الرياض',
            'city_en' => 'Riyadh',
            'is_active' => true,
        ];
    }
}

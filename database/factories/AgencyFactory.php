<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => fake('ar_SA')->company(),
            'name_en' => fake()->company(),
            'country' => fake()->randomElement(['PH', 'KE', 'UG', 'ET', 'LK']),
            'email' => fake()->companyEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'is_active' => true,
        ];
    }
}

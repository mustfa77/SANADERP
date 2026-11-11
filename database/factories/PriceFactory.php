<?php

namespace Database\Factories;

use App\Enums\RentalType;
use App\Models\Country;
use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'rental_type' => RentalType::Monthly,
            'price' => fake()->randomElement([1800, 2100, 2400, 2800]),
            'vat_percent' => 15,
            'is_active' => true,
        ];
    }

    public function hourly(): static
    {
        return $this->state(['rental_type' => RentalType::Hourly, 'price' => 35]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Profession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profession>
 */
class ProfessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $job = fake()->unique()->jobTitle();

        return [
            'name_ar' => $job.' (ar)',
            'name_en' => $job,
            'is_active' => true,
        ];
    }
}

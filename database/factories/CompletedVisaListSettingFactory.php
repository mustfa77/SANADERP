<?php

namespace Database\Factories;

use App\Models\Authority;
use App\Models\CompletedVisaListSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompletedVisaListSetting>
 */
class CompletedVisaListSettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'authority_id' => Authority::factory(),
            'country_id' => fn (array $attributes) => Authority::query()->find($attributes['authority_id'])?->country_id,
            'visas_count' => 30,
            'is_active' => true,
        ];
    }
}

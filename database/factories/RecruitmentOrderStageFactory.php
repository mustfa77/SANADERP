<?php

namespace Database\Factories;

use App\Models\RecruitmentOrder;
use App\Models\RecruitmentOrderStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentOrderStage>
 */
class RecruitmentOrderStageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recruitment_order_id' => RecruitmentOrder::factory(),
            'name_ar' => 'مرحلة',
            'name_en' => 'Stage',
            'sort_order' => 1,
            'max_days' => 7,
        ];
    }
}

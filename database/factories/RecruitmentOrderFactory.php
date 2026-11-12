<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Country;
use App\Models\Profession;
use App\Models\RecruitmentOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A new order (status "new"). Later steps go through RecruitmentService.
 *
 * @extends Factory<RecruitmentOrder>
 */
class RecruitmentOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'branch_id' => fn (array $attributes) => Client::query()->withoutGlobalScopes()->find($attributes['client_id'])?->branch_id,
            'country_id' => Country::factory(),
            'profession_id' => Profession::factory(),
            'contract_amount' => fake()->randomElement([12000, 13500, 14500, 16000]),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (RecruitmentOrder $order): void {
            $order->number ??= 'RQ-'.now()->format('Y').'-'.fake()->unique()->numerify('9###');
        });
    }
}

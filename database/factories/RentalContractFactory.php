<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\RentalContract;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An active one-month contract (rows only; the real flow, including the
 * worker status, goes through RentalContractService).
 *
 * @extends Factory<RentalContract>
 */
class RentalContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = today()->subDays(fake()->numberBetween(0, 20));

        return [
            'client_id' => Client::factory(),
            'worker_id' => Worker::factory(),
            'branch_id' => fn (array $attributes) => Client::query()->withoutGlobalScopes()->find($attributes['client_id'])?->branch_id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMonthNoOverflow()->subDay(),
            'price' => 2000,
            'discount' => 0,
            'vat_percent' => 15,
            'vat_amount' => 300,
            'total' => 2300,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (RentalContract $contract): void {
            $contract->number ??= 'MR-'.now()->format('Y').'-'.fake()->unique()->numerify('9###');
        });
    }
}

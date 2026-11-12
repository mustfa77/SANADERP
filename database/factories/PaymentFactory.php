<?php

namespace Database\Factories;

use App\Enums\LookupType;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An unverified payment on a rental contract.
 *
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payable_type' => RentalContract::class,
            'payable_id' => RentalContract::factory(),
            'branch_id' => fn (array $attributes) => RentalContract::query()->withoutGlobalScopes()->find($attributes['payable_id'])?->branch_id,
            'amount' => 500,
            'paid_at' => today(),
            'payment_method_id' => Lookup::factory()->state(['type' => LookupType::PaymentMethod]),
        ];
    }
}

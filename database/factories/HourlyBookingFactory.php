<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\HourlyBooking;
use App\Models\Shift;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A booked 4-hour shift tomorrow (rows only; the real flow goes through
 * HourlyBookingService).
 *
 * @extends Factory<HourlyBooking>
 */
class HourlyBookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = today()->addDay()->setTime(8, 0);

        return [
            'client_id' => Client::factory(),
            'worker_id' => Worker::factory(),
            'shift_id' => Shift::factory(),
            'branch_id' => fn (array $attributes) => Client::query()->withoutGlobalScopes()->find($attributes['client_id'])?->branch_id,
            'date' => $start->copy()->startOfDay(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(4),
            'hours' => 4,
            'hourly_price' => 50,
            'price' => 200,
            'discount' => 0,
            'vat_percent' => 15,
            'vat_amount' => 30,
            'total' => 230,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (HourlyBooking $booking): void {
            $booking->number ??= 'HB-'.now()->format('Y').'-'.fake()->unique()->numerify('9###');
        });
    }
}

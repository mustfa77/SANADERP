<?php

namespace Tests\Feature\Services\Rental;

use App\Enums\HourlyBookingStatus;
use App\Enums\LookupType;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Client;
use App\Models\Country;
use App\Models\Lookup;
use App\Models\Price;
use App\Models\Shift;
use App\Models\Worker;
use App\Services\Rental\HourlyBookingService;
use App\Services\Rental\RentalException;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HourlyBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private HourlyBookingService $service;

    private Client $client;

    private Country $philippines;

    private Shift $morning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HourlyBookingService::class);
        $this->client = Client::factory()->create();
        $this->philippines = Country::factory()->create(['code' => 'PH']);
        Price::factory()->hourly()->create(['country_id' => $this->philippines->id, 'price' => 40, 'vat_percent' => 15]);
        $this->morning = Shift::factory()->create(['start_time' => '08:00:00', 'hours' => 4]);
    }

    private function worker(array $attributes = []): Worker
    {
        $worker = Worker::factory()->create([
            'branch_id' => $this->client->branch_id,
            'country_id' => $this->philippines->id,
            'sponsor_type' => SponsorType::Company,
            'rental_type' => WorkerRentalType::Hourly,
            ...$attributes,
        ]);

        return app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Worker $worker, array $overrides = []): array
    {
        return [
            'client_id' => $this->client->id,
            'worker_id' => $worker->id,
            'shift_id' => $this->morning->id,
            'date' => today()->addDay()->toDateString(),
            ...$overrides,
        ];
    }

    public function test_booking_is_priced_by_the_hour_and_does_not_change_the_worker_status(): void
    {
        $worker = $this->worker();

        $booking = $this->service->book($this->data($worker, ['discount' => 10]))->sole();

        $this->assertSame('HB-'.now()->format('Y').'-0001', $booking->number);
        $this->assertSame('08:00', $booking->starts_at->format('H:i'));
        $this->assertSame('12:00', $booking->ends_at->format('H:i'));
        // 40 × 4 = 160 - 10 = 150 + 15% = 172.50
        $this->assertSame('160.00', $booking->price);
        $this->assertSame('172.50', $booking->total);
        $this->assertSame(WorkerStatus::Available, $worker->fresh()->status);
    }

    public function test_only_hourly_workers_from_a_country_with_an_hourly_price(): void
    {
        $monthly = $this->worker(['rental_type' => WorkerRentalType::Monthly]);
        $kenyan = $this->worker(['country_id' => Country::factory()->create(['code' => 'KE'])->id]);

        $this->assertSame([], $this->service->eligibleWorkers()->whereKey([$monthly->id, $kenyan->id])->pluck('id')->all());

        foreach ([$monthly, $kenyan] as $worker) {
            try {
                $this->service->book($this->data($worker));
                $this->fail('Expected RentalException');
            } catch (RentalException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_a_worker_cannot_be_booked_twice_at_overlapping_times(): void
    {
        $worker = $this->worker();
        $this->service->book($this->data($worker));
        $overlapping = Shift::factory()->create(['start_time' => '10:00:00', 'hours' => 4]);
        $afternoon = Shift::factory()->create(['start_time' => '13:00:00', 'hours' => 4]);

        try {
            $this->service->book($this->data($worker, ['shift_id' => $overlapping->id]));
            $this->fail('Expected RentalException');
        } catch (RentalException $exception) {
            $this->assertStringContainsString(today()->addDay()->toDateString(), $exception->getMessage());
        }

        $this->assertCount(1, $this->service->book($this->data($worker, ['shift_id' => $afternoon->id])));
    }

    public function test_several_days_are_booked_at_once_or_not_at_all(): void
    {
        $worker = $this->worker();

        $bookings = $this->service->book($this->data($worker, ['days' => 3]));
        $this->assertSame(
            [today()->addDay()->toDateString(), today()->addDays(2)->toDateString(), today()->addDays(3)->toDateString()],
            $bookings->map(fn ($booking) => $booking->date->toDateString())->all(),
        );

        // Day 3 of this series clashes: nothing is saved.
        $other = $this->worker();
        $this->service->book($this->data($other, ['date' => today()->addDays(3)->toDateString()]));

        try {
            $this->service->book($this->data($other, ['days' => 5]));
            $this->fail('Expected RentalException');
        } catch (RentalException) {
            $this->assertSame(1, $other->hourlyBookings()->count());
        }
    }

    public function test_cancel_frees_the_worker_and_complete_needs_the_shift_started(): void
    {
        $worker = $this->worker();
        $booking = $this->service->book($this->data($worker))->sole();

        try {
            $this->service->complete($booking);
            $this->fail('Expected RentalException');
        } catch (RentalException) {
            $this->assertSame(HourlyBookingStatus::Booked, $booking->fresh()->status);
        }

        $reason = Lookup::factory()->create(['type' => LookupType::CancelReason]);
        $this->assertSame(HourlyBookingStatus::Cancelled, $this->service->cancel($booking, $reason)->status);
        $this->assertTrue($this->morning->isInUse());

        // The time is free again.
        $this->assertCount(1, $this->service->book($this->data($worker)));
    }

    public function test_past_dates_are_refused(): void
    {
        $this->expectException(RentalException::class);
        $this->service->book($this->data($this->worker(), ['date' => today()->subDay()->toDateString()]));
    }
}

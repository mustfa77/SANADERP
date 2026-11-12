<?php

namespace App\Services\Rental;

use App\Enums\HourlyBookingStatus;
use App\Enums\RentalType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Client;
use App\Models\HourlyBooking;
use App\Models\Lookup;
use App\Models\Shift;
use App\Models\Worker;
use App\Services\AuditLogger;
use App\Services\Transport\TripService;
use App\Support\SequenceNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Hourly bookings: one worker, one date, one shift. Only workers marked for
 * hourly rental, available, and whose country has an active hourly price
 * (Settings > Prices) can be booked. A worker is never booked twice at
 * overlapping times. The worker's status does not change.
 */
class HourlyBookingService
{
    public const MAX_DAYS = 31;

    public function __construct(private AuditLogger $audit, private RentalPricing $pricing, private RefundService $refunds, private TripService $trips) {}

    /**
     * @return Builder<Worker>
     */
    public function eligibleWorkers(): Builder
    {
        return Worker::query()
            ->where('status', WorkerStatus::Available)
            ->where('rental_type', WorkerRentalType::Hourly)
            ->whereIn('country_id', $this->pricing->countriesWithPrice(RentalType::Hourly));
    }

    /**
     * Hourly price of the worker's country × the shift hours, with VAT.
     *
     * @return array{hourly_price: float, hours: float, price: float, discount: float, vat_percent: float, vat_amount: float, total: float}
     */
    public function quote(Worker $worker, Shift $shift, float|string|null $hourlyPrice = null, float|string|null $discount = null): array
    {
        $active = $this->pricing->activePrice($worker->country_id, RentalType::Hourly);

        if (! filled($hourlyPrice) && ! $active) {
            throw RentalException::because('no_hourly_price');
        }

        $rate = filled($hourlyPrice) ? (float) $hourlyPrice : (float) $active->price;
        $hours = (float) $shift->hours;

        return [
            'hourly_price' => $rate,
            'hours' => $hours,
            ...$this->pricing->amounts($rate * $hours, (float) ($discount ?: 0), $active ? (float) $active->vat_percent : 15.0),
        ];
    }

    /**
     * Book the worker for the shift on $data['date'] and the following days
     * ($data['days'], 1 by default). All the bookings are saved or none.
     *
     * @param  array{client_id: int, worker_id: int, shift_id: int, date: string, days?: int|string|null, hourly_price?: float|string|null, discount?: float|string|null, address?: ?string, notes?: ?string}  $data
     * @return Collection<int, HourlyBooking>
     */
    public function book(array $data): Collection
    {
        return DB::transaction(function () use ($data): Collection {
            $client = Client::query()->findOrFail($data['client_id']);
            $worker = Worker::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($data['worker_id']);
            $shift = Shift::query()->active()->findOrFail($data['shift_id']);
            $days = max(1, min(self::MAX_DAYS, (int) ($data['days'] ?? 1)));

            if (! $this->eligibleWorkers()->withoutGlobalScopes()->whereKey($worker->getKey())->exists()) {
                throw RentalException::because('worker_not_hourly');
            }

            if ($worker->branch_id !== $client->branch_id) {
                throw RentalException::because('branch_mismatch');
            }

            $quote = $this->quote($worker, $shift, $data['hourly_price'] ?? null, $data['discount'] ?? null);
            $first = Carbon::parse($data['date'])->startOfDay();

            if ($first->lt(today())) {
                throw RentalException::because('date_in_past');
            }

            $bookings = collect();

            for ($day = 0; $day < $days; $day++) {
                $date = $first->copy()->addDays($day);
                [$start, $end] = $this->period($date, $shift);

                if ($this->hasOverlap($worker, $start, $end)) {
                    throw RentalException::because('worker_busy', ['date' => $date->toDateString(), 'shift' => $shift->trans('name')]);
                }

                $booking = new HourlyBooking(['address' => $data['address'] ?? null, 'notes' => $data['notes'] ?? null]);
                $booking->forceFill([
                    'number' => SequenceNumber::next(HourlyBooking::class, 'HB'),
                    'branch_id' => $client->branch_id,
                    'client_id' => $client->getKey(),
                    'worker_id' => $worker->getKey(),
                    'shift_id' => $shift->getKey(),
                    'date' => $date,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'created_by' => Auth::id(),
                    ...$quote,
                ])->save();

                $this->audit->log('hourly.created', $booking, $this->params($booking), new: $booking->only(['number', 'starts_at', 'ends_at', 'total']));
                $this->trips->forHourlyBooking($booking);
                $bookings->push($booking);
            }

            return $bookings;
        });
    }

    /**
     * Start and end of the shift on the date (an end before the start is
     * the next day).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function period(Carbon $date, Shift $shift): array
    {
        $start = Carbon::parse($date->toDateString().' '.$shift->start_time);
        $end = $start->copy()->addMinutes((int) round((float) $shift->hours * 60));

        return [$start, $end];
    }

    public function hasOverlap(Worker $worker, Carbon $start, Carbon $end, ?int $ignoreId = null): bool
    {
        return HourlyBooking::query()->withoutGlobalScopes()
            ->where('worker_id', $worker->getKey())
            ->where('status', '!=', HourlyBookingStatus::Cancelled)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    /**
     * The worker leaves the office for the booking (date and time).
     */
    public function depart(HourlyBooking $booking, string $departedAt): HourlyBooking
    {
        return DB::transaction(function () use ($booking, $departedAt): HourlyBooking {
            $booking = $this->lock($booking);
            $out = Carbon::parse($departedAt);

            if ($out->lt($booking->starts_at->copy()->startOfDay()) || $out->isFuture()) {
                throw RentalException::because('departure_out_of_range');
            }

            $booking->forceFill(['departed_at' => $out])->save();

            $this->audit->log('hourly.departed', $booking, $this->params($booking), new: ['departed_at' => $out->toDateTimeString()]);

            return $booking;
        });
    }

    /**
     * The worker is back: the booking is completed. The departure time is
     * recorded too if it was not yet. A return after the end of the shift
     * is shown as a delay (no extra charge).
     */
    public function complete(HourlyBooking $booking, ?string $returnedAt = null, ?string $departedAt = null): HourlyBooking
    {
        return DB::transaction(function () use ($booking, $returnedAt, $departedAt): HourlyBooking {
            $booking = $this->lock($booking);

            if ($booking->starts_at->isFuture()) {
                throw RentalException::because('not_started');
            }

            $back = $returnedAt ? Carbon::parse($returnedAt) : now()->startOfMinute();
            $out = $departedAt ? Carbon::parse($departedAt) : ($booking->departed_at ?? $booking->starts_at);

            if ($back->lt($out) || $back->isAfter(now()->addMinute())) {
                throw RentalException::because('return_before_departure');
            }

            $booking->forceFill(['departed_at' => $out, 'returned_at' => $back, 'completed_at' => now()]);
            $this->setStatus($booking, HourlyBookingStatus::Completed);

            $this->audit->log('hourly.completed', $booking, $this->params($booking), new: [
                'departed_at' => $out->toDateTimeString(),
                'returned_at' => $back->toDateTimeString(),
                'late_minutes' => $booking->lateMinutes(),
            ]);

            return $booking;
        });
    }

    /**
     * Cancel the booking: nothing is due and what was paid is refunded
     * (refund request; $refund overrides the amount, 0 = none).
     */
    public function cancel(HourlyBooking $booking, Lookup $reason, ?string $note = null, float|string|null $refund = null): HourlyBooking
    {
        return DB::transaction(function () use ($booking, $reason, $note, $refund): HourlyBooking {
            $booking = $this->lock($booking);

            $booking->forceFill(['cancelled_at' => now(), 'cancel_reason_id' => $reason->getKey(), 'cancel_note' => $note, 'final_total' => 0]);
            $this->setStatus($booking, HourlyBookingStatus::Cancelled);
            $this->trips->cancelFor($booking, __('hourly.refund_reason', ['number' => $booking->number]));

            $amount = filled($refund) ? round((float) $refund, 2) : $this->refunds->refundable($booking);

            if ($amount > 0) {
                $this->refunds->request($booking, $amount, __('hourly.refund_reason', ['number' => $booking->number]));
            }

            $this->audit->log('hourly.cancelled', $booking, [...$this->params($booking), 'reason' => $reason->translations('name')], new: ['note' => $note, 'refund' => $amount]);

            return $booking;
        });
    }

    private function lock(HourlyBooking $booking): HourlyBooking
    {
        $locked = HourlyBooking::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($booking->getKey());

        if ($locked->status !== HourlyBookingStatus::Booked) {
            throw RentalException::because('booking_closed', ['status' => $locked->status->label()]);
        }

        return $locked;
    }

    private function setStatus(HourlyBooking $booking, HourlyBookingStatus $status): void
    {
        HourlyBooking::withStatusChange(fn () => $booking->forceFill(['status' => $status])->save());
    }

    /**
     * @return array<string, mixed>
     */
    private function params(HourlyBooking $booking): array
    {
        return ['number' => $booking->number, 'client' => $booking->client?->translations('name'), 'worker' => $booking->worker?->translations('name')];
    }
}

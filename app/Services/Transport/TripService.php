<?php

namespace App\Services\Transport;

use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\UserType;
use App\Models\HourlyBooking;
use App\Models\RecruitmentOrder;
use App\Models\Trip;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\SequenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Trips: created automatically (hourly booking → drop-off at the shift's
 * pick-up time and return at its end; recruitment home delivery), or by the
 * coordinator. Assigned to a driver of the branch; the driver starts and
 * completes them.
 */
class TripService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Drop-off and return trips of an hourly booking.
     *
     * @return Collection<int, Trip>
     */
    public function forHourlyBooking(HourlyBooking $booking): Collection
    {
        $booking->loadMissing(['worker.camp', 'client', 'shift', 'branch']);
        $base = $booking->worker?->camp?->trans('name') ?? $booking->branch?->trans('name');
        $client = trim(($booking->address ?: '').' — '.$booking->client?->trans('name'), ' —');
        $dropoff = $booking->shift?->move_time
            ? Carbon::parse($booking->date->toDateString().' '.$booking->shift->move_time)
            : $booking->starts_at->copy()->subMinutes(45);

        return collect([
            $this->create($booking, TripType::HourlyDropoff, $dropoff, $base, $client),
            $this->create($booking, TripType::HourlyPickup, $booking->ends_at->copy(), $client, $base),
        ]);
    }

    /**
     * Home delivery of a recruited worker to the client.
     */
    public function forHomeDelivery(RecruitmentOrder $order): Trip
    {
        $order->loadMissing(['client', 'arrivalCamp', 'branch']);
        $to = trim(collect([$order->client?->city, $order->client?->district, $order->client?->trans('name')])->filter()->join(' — '));

        return $this->create($order, TripType::Delivery, now()->addHour()->startOfHour(), $order->arrivalCamp?->trans('name') ?? $order->branch?->trans('name'), $to);
    }

    /**
     * Manual trip by the transport coordinator.
     *
     * @param  array{branch_id: int, scheduled_at: string, from_place?: ?string, to_place?: ?string, worker_id?: ?int, client_id?: ?int, driver_id?: ?int, notes?: ?string}  $data
     */
    public function createManual(array $data): Trip
    {
        return DB::transaction(function () use ($data): Trip {
            if (filled($data['driver_id'] ?? null)) {
                $this->checkDriver((int) $data['driver_id'], (int) $data['branch_id']);
            }

            $trip = new Trip(['from_place' => $data['from_place'] ?? null, 'to_place' => $data['to_place'] ?? null, 'notes' => $data['notes'] ?? null]);
            $trip->forceFill([
                'number' => SequenceNumber::next(Trip::class, 'TR'),
                'type' => TripType::Manual,
                'branch_id' => $data['branch_id'],
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'worker_id' => $data['worker_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'status' => TripStatus::Scheduled,
                'created_by' => Auth::id(),
            ])->save();

            $this->audit->log('trip.created', $trip, ['number' => $trip->number], new: $trip->only(['scheduled_at', 'from_place', 'to_place']));

            return $trip;
        });
    }

    public function assign(Trip $trip, ?User $driver): Trip
    {
        if (! $trip->status->isOpen()) {
            throw new TransportException(__('transport.errors.closed'));
        }

        if ($driver) {
            $this->checkDriver($driver->getKey(), $trip->branch_id);
        }

        $old = $trip->driver_id;
        $trip->forceFill(['driver_id' => $driver?->getKey()])->save();

        $this->audit->log('trip.assigned', $trip, ['number' => $trip->number, 'driver' => $driver?->displayName() ?? '—'], ['driver_id' => $old], ['driver_id' => $driver?->getKey()]);

        return $trip;
    }

    /**
     * The driver left (only the assigned driver, or staff for them).
     */
    public function start(Trip $trip): Trip
    {
        if ($trip->status !== TripStatus::Scheduled) {
            throw new TransportException(__('transport.errors.not_scheduled'));
        }

        if (! $trip->driver_id) {
            throw new TransportException(__('transport.errors.no_driver'));
        }

        $trip->forceFill(['status' => TripStatus::Started, 'started_at' => now()])->save();
        $this->audit->log('trip.started', $trip, ['number' => $trip->number]);

        return $trip;
    }

    public function complete(Trip $trip): Trip
    {
        if (! $trip->status->isOpen()) {
            throw new TransportException(__('transport.errors.closed'));
        }

        if (! $trip->driver_id) {
            throw new TransportException(__('transport.errors.no_driver'));
        }

        $trip->forceFill(['status' => TripStatus::Completed, 'started_at' => $trip->started_at ?? now(), 'completed_at' => now()])->save();
        $this->audit->log('trip.completed', $trip, ['number' => $trip->number]);

        return $trip;
    }

    public function cancel(Trip $trip, ?string $note = null): Trip
    {
        if (! $trip->status->isOpen()) {
            throw new TransportException(__('transport.errors.closed'));
        }

        $trip->forceFill(['status' => TripStatus::Cancelled, 'cancelled_at' => now(), 'cancel_note' => $note])->save();
        $this->audit->log('trip.cancelled', $trip, ['number' => $trip->number], new: ['note' => $note]);

        return $trip;
    }

    /**
     * Cancel the open trips of a booking / order that was cancelled.
     */
    public function cancelFor(Model $related, string $note): int
    {
        $trips = Trip::query()->withoutGlobalScopes()->whereMorphedTo('related', $related)
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::Started])->get();

        $trips->each(fn (Trip $trip) => $this->cancel($trip, $note));

        return $trips->count();
    }

    /**
     * Active drivers of the branch.
     *
     * @return Collection<int, User>
     */
    public function drivers(int $branchId): Collection
    {
        return User::query()
            ->where('user_type', UserType::Driver)
            ->whereHas('driverProfile', fn ($query) => $query->withoutGlobalScopes()->where('branch_id', $branchId)->where('is_active', true))
            ->orderBy('name_ar')
            ->get();
    }

    private function create(Model $related, TripType $type, Carbon $at, ?string $from, ?string $to): Trip
    {
        $trip = new Trip(['from_place' => $from, 'to_place' => $to]);
        $trip->forceFill([
            'number' => SequenceNumber::next(Trip::class, 'TR'),
            'type' => $type,
            'branch_id' => $related->branch_id,
            'worker_id' => $related->worker_id,
            'client_id' => $related->client_id,
            'scheduled_at' => $at,
            'status' => TripStatus::Scheduled,
            'created_by' => Auth::id(),
        ]);
        $trip->related()->associate($related);
        $trip->save();

        return $trip;
    }

    private function checkDriver(int $driverId, int $branchId): void
    {
        if (! $this->drivers($branchId)->contains('id', $driverId)) {
            throw new TransportException(__('transport.errors.driver_not_allowed'));
        }
    }
}

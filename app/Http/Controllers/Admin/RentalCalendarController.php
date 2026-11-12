<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HourlyBookingStatus;
use App\Enums\RentalContractStatus;
use App\Http\Controllers\Controller;
use App\Models\HourlyBooking;
use App\Models\RentalContract;
use App\Services\Rental\HourlyBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Rental calendar (branch scoped): hourly bookings at their times and the
 * start / end days of monthly contracts.
 */
class RentalCalendarController extends Controller
{
    private const COLORS = [
        'booked' => '#3b5de7',
        'completed' => '#1f9d55',
        'cancelled' => '#adb5bd',
        'contract_start' => '#0bb197',
        'contract_end' => '#f1b44c',
        'contract_overdue' => '#f46a6a',
    ];

    public function index(Request $request, HourlyBookingService $bookings): View
    {
        $this->authorizeAny($request);

        return view('admin.rental.calendar', [
            'colors' => self::COLORS,
            'workers' => $request->user()->can('hourly_bookings.view')
                ? $bookings->eligibleWorkers()->orderBy('name_en')->get()
                : collect(),
        ]);
    }

    /**
     * FullCalendar event feed (?start=&end= ISO dates).
     */
    public function events(Request $request): JsonResponse
    {
        $this->authorizeAny($request);
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'worker_id' => ['nullable', 'integer'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:bookings,contracts'],
        ]);

        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end'])->min($start->copy()->addDays(62));
        $types = $validated['types'] ?? ['bookings', 'contracts'];
        $events = collect();

        if (in_array('bookings', $types, true) && $request->user()->can('hourly_bookings.view')) {
            $events = $events->concat(HourlyBooking::query()
                ->whereBetween('starts_at', [$start, $end])
                ->when($validated['worker_id'] ?? null, fn ($query, $worker) => $query->where('worker_id', $worker))
                ->with(['worker', 'client'])
                ->limit(2000)
                ->get()
                ->map(fn (HourlyBooking $booking): array => [
                    'id' => 'hb-'.$booking->id,
                    'title' => $booking->worker?->trans('name').' — '.$booking->client?->trans('name'),
                    'start' => $booking->starts_at->toIso8601String(),
                    'end' => $booking->ends_at->toIso8601String(),
                    'url' => route('admin.hourly.show', $booking),
                    'color' => self::COLORS[$booking->status->value],
                    'classNames' => $booking->status === HourlyBookingStatus::Cancelled ? ['text-decoration-line-through'] : [],
                    'extendedProps' => ['kind' => 'booking', 'status' => $booking->status->label(), 'number' => $booking->number],
                ]));
        }

        if (in_array('contracts', $types, true) && $request->user()->can('rental_contracts.view')) {
            $contracts = RentalContract::query()
                ->where(fn ($query) => $query
                    ->whereBetween('starts_at', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('ends_at', [$start->toDateString(), $end->toDateString()]))
                ->when($validated['worker_id'] ?? null, fn ($query, $worker) => $query->where('worker_id', $worker))
                ->with(['worker', 'client'])
                ->limit(2000)
                ->get();

            foreach ($contracts as $contract) {
                $label = $contract->worker?->trans('name').' — '.$contract->client?->trans('name');

                if ($contract->starts_at->between($start, $end)) {
                    $events->push($this->contractEvent($contract, 'start', $contract->starts_at, '▶ '.$label));
                }

                if ($contract->ends_at->between($start, $end)) {
                    $overdue = $contract->status === RentalContractStatus::Active && $contract->ends_at->isPast() && ! $contract->ends_at->isToday();
                    $events->push($this->contractEvent($contract, $overdue ? 'overdue' : 'end', $contract->ends_at, '⏹ '.$label));
                }
            }
        }

        return response()->json($events->values());
    }

    /**
     * @return array<string, mixed>
     */
    private function contractEvent(RentalContract $contract, string $kind, Carbon $date, string $title): array
    {
        return [
            'id' => 'mr-'.$contract->id.'-'.$kind,
            'title' => $title,
            'start' => $date->toDateString(),
            'allDay' => true,
            'url' => route('admin.rental.show', $contract),
            'color' => self::COLORS['contract_'.$kind],
            'extendedProps' => ['kind' => 'contract_'.$kind, 'status' => $contract->status->label(), 'number' => $contract->number],
        ];
    }

    private function authorizeAny(Request $request): void
    {
        abort_unless($request->user()->can('rental_contracts.view') || $request->user()->can('hourly_bookings.view'), 403);
    }
}

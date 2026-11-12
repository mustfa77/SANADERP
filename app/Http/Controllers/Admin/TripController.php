<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Trip;
use App\Models\User;
use App\Models\Worker;
use App\Services\Transport\TransportException;
use App\Services\Transport\TripService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Trips board for the transport coordinator (branch scoped): the day's
 * trips with driver assignment, manual trips, start / complete / cancel.
 */
class TripController extends Controller
{
    public function __construct(private TripService $trips) {}

    public function index(Request $request): View
    {
        $date = rescue(fn () => Carbon::parse((string) $request->query('date', today()->toDateString()))->startOfDay(), today(), false);
        $filters = $request->only(['status', 'type', 'driver_id']);
        $branchIds = $request->user()->allowedBranchIds();

        $trips = Trip::query()
            ->whereDate('scheduled_at', $date)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when(($filters['driver_id'] ?? null) === 'none', fn (Builder $query) => $query->whereNull('driver_id'))
            ->when(is_numeric($filters['driver_id'] ?? null), fn (Builder $query) => $query->where('driver_id', $filters['driver_id']))
            ->with(['driver', 'worker', 'client', 'branch'])
            ->orderBy('scheduled_at')
            ->get();

        $drivers = Branch::query()->whereKey($branchIds)->get()
            ->mapWithKeys(fn (Branch $branch): array => [$branch->id => $this->trips->drivers($branch->id)]);

        return view('admin.trips.index', [
            'trips' => $trips,
            'date' => $date,
            'filters' => $filters,
            'drivers' => $drivers,
            'allDrivers' => $drivers->flatten()->unique('id')->values(),
            'statuses' => TripStatus::cases(),
            'types' => TripType::cases(),
            'branches' => Branch::query()->whereKey($branchIds)->where('is_active', true)->orderBy('code')->get(),
            'workers' => Worker::query()->orderBy('name_en')->limit(500)->get(['id', 'name_ar', 'name_en', 'branch_id']),
            'clients' => Client::query()->active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'phone']),
            'unassigned' => $trips->where('status', TripStatus::Scheduled)->whereNull('driver_id')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::in($request->user()->allowedBranchIds())],
            'scheduled_at' => ['required', 'date'],
            'from_place' => ['required', 'string', 'max:500'],
            'to_place' => ['required', 'string', 'max:500'],
            'worker_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], collect(['branch_id', 'scheduled_at', 'from_place', 'to_place', 'worker_id', 'client_id', 'driver_id', 'notes'])
            ->mapWithKeys(fn (string $field): array => [$field => __("transport.fields.{$field}")])->all());

        // Only records the user may see.
        if (filled($validated['worker_id'] ?? null)) {
            Worker::query()->findOrFail($validated['worker_id']);
        }
        if (filled($validated['client_id'] ?? null)) {
            Client::query()->findOrFail($validated['client_id']);
        }

        try {
            $trip = $this->trips->createManual($validated);
        } catch (TransportException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.trips.index', ['date' => $trip->scheduled_at->toDateString()])->with('success', __('transport.flash.created'));
    }

    public function assign(Request $request, Trip $trip): RedirectResponse
    {
        $validated = $request->validate(['driver_id' => ['nullable', 'integer']]);
        $driver = filled($validated['driver_id'] ?? null) ? User::query()->findOrFail($validated['driver_id']) : null;

        return $this->attempt(fn () => $this->trips->assign($trip, $driver), $trip, 'transport.flash.assigned');
    }

    public function start(Trip $trip): RedirectResponse
    {
        return $this->attempt(fn () => $this->trips->start($trip), $trip, 'transport.flash.started');
    }

    public function complete(Trip $trip): RedirectResponse
    {
        return $this->attempt(fn () => $this->trips->complete($trip), $trip, 'transport.flash.completed');
    }

    public function cancel(Request $request, Trip $trip): RedirectResponse
    {
        $note = $request->validate(['cancel_note' => ['nullable', 'string', 'max:500']])['cancel_note'] ?? null;

        return $this->attempt(fn () => $this->trips->cancel($trip, $note), $trip, 'transport.flash.cancelled');
    }

    /**
     * @param  callable(): mixed  $step
     */
    private function attempt(callable $step, Trip $trip, string $message): RedirectResponse
    {
        try {
            $step();
        } catch (TransportException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.trips.index', ['date' => $trip->scheduled_at->toDateString()])->with('success', __($message));
    }
}

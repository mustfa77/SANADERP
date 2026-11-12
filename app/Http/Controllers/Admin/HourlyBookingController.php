<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HourlyBookingStatus;
use App\Enums\LookupType;
use App\Enums\RentalType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\HourlyBooking;
use App\Models\Lookup;
use App\Models\Shift;
use App\Models\Worker;
use App\Services\Rental\HourlyBookingService;
use App\Services\Rental\RentalException;
use App\Services\Rental\RentalPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Hourly bookings (only workers marked for hourly rental whose country has
 * an hourly price). Every step goes through HourlyBookingService.
 */
class HourlyBookingController extends Controller
{
    public function __construct(private HourlyBookingService $bookings) {}

    /**
     * Bookings from a date (today by default), grouped by day.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'branch_id', 'worker_id']);
        $from = Carbon::parse($request->query('from') ?: today()->toDateString())->startOfDay();
        $to = Carbon::parse($request->query('to') ?: $from->copy()->addDays(6)->toDateString())->endOfDay();

        $bookings = HourlyBooking::query()
            ->whereBetween('starts_at', [$from, $to])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('number', 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $client) => $client->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                ->orWhereHas('worker', fn (Builder $worker) => $worker->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['branch_id'] ?? null, fn (Builder $query, string $branch) => $query->where('branch_id', $branch))
            ->when($filters['worker_id'] ?? null, fn (Builder $query, string $worker) => $query->where('worker_id', $worker))
            ->with(['client', 'worker', 'shift'])
            ->withPaymentTotals()
            ->orderBy('starts_at')
            ->limit(500)
            ->get();

        return view('admin.hourly.index', [
            'days' => $bookings->groupBy(fn (HourlyBooking $booking): string => $booking->date->toDateString()),
            'count' => $bookings->count(),
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'statuses' => HourlyBookingStatus::cases(),
            'branches' => Branch::query()->whereKey($request->user()->allowedBranchIds())->orderBy('code')->get(),
            'workers' => $this->bookings->eligibleWorkers()->orderBy('name_en')->get(),
        ]);
    }

    public function create(Request $request, RentalPricing $pricing): View
    {
        $workers = $this->bookings->eligibleWorkers()->with(['country', 'branch'])->orderBy('name_en')->get();

        return view('admin.hourly.create', [
            'clients' => Client::query()->active()->orderBy('name_ar')->get(),
            'workers' => $workers,
            'shifts' => Shift::query()->active()->ordered()->get(),
            'rates' => $workers->pluck('country_id')->unique()->mapWithKeys(fn ($countryId): array => [$countryId => $pricing->activePrice($countryId, RentalType::Hourly)]),
            'hourlyCountries' => $pricing->countriesWithPrice(RentalType::Hourly),
            'selectedClient' => $request->integer('client') ?: null,
            'selectedWorker' => $request->integer('worker') ?: null,
            'maxDays' => HourlyBookingService::MAX_DAYS,
            'selectedDate' => rescue(fn () => Carbon::parse((string) $request->query('date'))->max(today())->toDateString(), today()->toDateString(), false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->whereIn('branch_id', $request->user()->allowedBranchIds())],
            'worker_id' => ['required', 'integer'],
            'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'days' => ['nullable', 'integer', 'min:1', 'max:'.HourlyBookingService::MAX_DAYS],
            'hourly_price' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], collect(['client_id', 'worker_id', 'shift_id', 'date', 'days', 'hourly_price', 'discount', 'address', 'notes'])
            ->mapWithKeys(fn (string $field): array => [$field => __("hourly.fields.{$field}")])->all());

        Worker::query()->findOrFail($validated['worker_id']);

        try {
            $bookings = $this->bookings->book($validated);
        } catch (RentalException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return $bookings->count() === 1
            ? redirect()->route('admin.hourly.show', $bookings->first())->with('success', trans_choice('hourly.flash.created', 1))
            : redirect()->route('admin.hourly.index', ['from' => $bookings->first()->date->toDateString(), 'to' => $bookings->last()->date->toDateString(), 'worker_id' => $bookings->first()->worker_id])
                ->with('success', trans_choice('hourly.flash.created', $bookings->count(), ['count' => $bookings->count()]));
    }

    public function show(HourlyBooking $booking): View
    {
        $booking->load(['client', 'worker.country', 'shift', 'branch', 'cancelReason', 'creator', 'payments.method', 'payments.receiver', 'payments.verifier', 'refunds.requester', 'refunds.decider']);

        return view('admin.hourly.show', [
            'booking' => $booking,
            'cancelReasons' => Lookup::query()->ofType(LookupType::CancelReason)->active()->ordered()->get(),
        ]);
    }

    /**
     * The worker left for the booking.
     */
    public function depart(Request $request, HourlyBooking $booking): RedirectResponse
    {
        $validated = $request->validate(['departed_at' => ['required', 'date']], [], ['departed_at' => __('hourly.fields.departed_at')]);

        return $this->attempt(fn () => $this->bookings->depart($booking, $validated['departed_at']), $booking, 'hourly.flash.departed');
    }

    /**
     * The worker is back: the booking is completed.
     */
    public function complete(Request $request, HourlyBooking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'returned_at' => ['required', 'date'],
            'departed_at' => ['nullable', 'date'],
        ], [], ['returned_at' => __('hourly.fields.returned_at'), 'departed_at' => __('hourly.fields.departed_at')]);

        return $this->attempt(fn () => $this->bookings->complete($booking, $validated['returned_at'], $validated['departed_at'] ?? null), $booking, 'hourly.flash.completed');
    }

    public function cancel(Request $request, HourlyBooking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason_id' => ['required', Rule::exists('lookups', 'id')->where('type', LookupType::CancelReason->value)->whereNull('deleted_at')],
            'cancel_note' => ['nullable', 'string', 'max:500'],
            'refund' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ], [], ['cancel_reason_id' => __('hourly.fields.cancel_reason_id'), 'cancel_note' => __('hourly.fields.cancel_note'), 'refund' => __('refunds.fields.amount')]);

        return $this->attempt(
            fn () => $this->bookings->cancel($booking, Lookup::query()->findOrFail($validated['cancel_reason_id']), $validated['cancel_note'] ?? null, $validated['refund'] ?? null),
            $booking,
            'hourly.flash.cancelled',
        );
    }

    /**
     * @param  callable(): mixed  $step
     */
    private function attempt(callable $step, HourlyBooking $booking, string $message): RedirectResponse
    {
        try {
            $step();
        } catch (RentalException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.hourly.show', $booking)->with('success', __($message));
    }
}

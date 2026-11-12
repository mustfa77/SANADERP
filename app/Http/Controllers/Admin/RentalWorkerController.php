<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HourlyBookingStatus;
use App\Enums\RentalType;
use App\Enums\WorkerRentalType;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\HourlyBooking;
use App\Models\Profession;
use App\Models\Worker;
use App\Services\Rental\RentalException;
use App\Services\Rental\RentalPricing;
use App\Services\Rental\RentalWorkerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rental pool (branch scoped): workers available for monthly rental, for
 * hourly bookings, and company workers not assigned to rental yet, with
 * one-click "add to monthly / hourly" and booking buttons.
 */
class RentalWorkerController extends Controller
{
    private const TABS = ['monthly', 'hourly', 'unassigned'];

    public function __construct(private RentalWorkerService $pool, private RentalPricing $pricing) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can('rental_contracts.view') || $user->can('hourly_bookings.view'), 403);

        $filters = $request->only(['search', 'country_id', 'profession_id']);
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : ($user->can('rental_contracts.view') ? 'monthly' : 'hourly');
        $hourlyCountries = $this->pricing->countriesWithPrice(RentalType::Hourly);

        $base = fn (): Builder => $this->filtered($this->pool->availableCompanyWorkers(), $filters);
        $queries = [
            'monthly' => fn (): Builder => $base()->where('rental_type', WorkerRentalType::Monthly),
            'hourly' => fn (): Builder => $base()->where('rental_type', WorkerRentalType::Hourly)->whereIn('country_id', $hourlyCountries),
            'unassigned' => fn (): Builder => $base()->where(fn (Builder $query) => $query
                ->where('rental_type', WorkerRentalType::None)
                // Hourly workers of a country without an hourly price cannot be booked: offer them again.
                ->orWhere(fn (Builder $hourly) => $hourly->where('rental_type', WorkerRentalType::Hourly)->whereNotIn('country_id', $hourlyCountries))),
        ];

        $workers = $queries[$tab]()
            ->with(['country', 'profession'])
            ->when($tab === 'hourly', fn (Builder $query) => $query->with(['hourlyBookings' => fn (HasMany $bookings) => $bookings
                ->where('status', HourlyBookingStatus::Booked)->where('ends_at', '>', now())->reorder('starts_at')->with('shift')]))
            ->orderBy('name_en')
            ->paginate(30)
            ->withQueryString();

        return view('admin.rental.workers', [
            'workers' => $workers,
            'tab' => $tab,
            'counts' => collect($queries)->map(fn ($query): int => $query()->count())->all(),
            'filters' => $filters,
            'hourlyCountries' => $hourlyCountries,
            'countries' => Country::query()->ordered()->get(),
            'professions' => Profession::query()->ordered()->get(),
            'todayBookings' => HourlyBooking::query()->whereDate('date', today())->where('status', '!=', HourlyBookingStatus::Cancelled)->count(),
        ]);
    }

    /**
     * Add the worker to the monthly / hourly pool, or take her out ("none").
     */
    public function assign(Request $request, Worker $worker): RedirectResponse
    {
        $validated = $request->validate(['rental_type' => ['required', Rule::enum(WorkerRentalType::class)]]);
        $type = WorkerRentalType::from($validated['rental_type']);

        $permission = match ($type) {
            WorkerRentalType::Hourly => 'hourly_bookings.create',
            WorkerRentalType::Monthly => 'rental_contracts.create',
            WorkerRentalType::None => $worker->rental_type === WorkerRentalType::Hourly ? 'hourly_bookings.create' : 'rental_contracts.create',
        };
        abort_unless($request->user()->can($permission), 403);

        try {
            $this->pool->assign($worker, $type);
        } catch (RentalException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('rental.pool.flash.'.$type->value, ['name' => $worker->trans('name')]));
    }

    /**
     * @param  Builder<Worker>  $query
     * @param  array<string, string|null>  $filters
     * @return Builder<Worker>
     */
    private function filtered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $id = preg_match('/^W-?0*(\d+)$/i', trim($search), $match) ? (int) $match[1] : null;

                $query->where(fn (Builder $q) => $q
                    ->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('cv_reference_no', 'like', "%{$search}%")
                    ->when($id, fn (Builder $byId) => $byId->orWhere('id', $id)));
            })
            ->when($filters['country_id'] ?? null, fn (Builder $query, string $country) => $query->where('country_id', $country))
            ->when($filters['profession_id'] ?? null, fn (Builder $query, string $profession) => $query->where('profession_id', $profession));
    }
}

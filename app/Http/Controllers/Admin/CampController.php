<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\CampMovementType;
use App\Http\Controllers\Controller;
use App\Models\Camp;
use App\Models\CampAttendance;
use App\Models\CampMovement;
use App\Models\Worker;
use App\Services\Camps\CampException;
use App\Services\Camps\CampService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Housing operations (branch scoped): occupancy, workers inside, manual
 * movements, daily roll-call and the movements log.
 */
class CampController extends Controller
{
    private const TABS = ['workers', 'attendance', 'movements'];

    public function __construct(private CampService $camps) {}

    public function index(): View
    {
        $today = today();
        $camps = Camp::query()->active()->with('branch')->withCount('workers')->orderBy('branch_id')->orderBy('name_ar')->get();
        $attendance = CampAttendance::query()->whereDate('date', $today)->get()->groupBy('camp_id');

        return view('admin.camps.index', [
            'camps' => $camps,
            'attendance' => $attendance,
            'movementsToday' => CampMovement::query()->whereDate('moved_at', $today)->count(),
            'unhoused' => Worker::query()->whereIn('status', CampService::HOUSED_STATUSES)->whereNull('camp_id')->count(),
        ]);
    }

    public function show(Request $request, Camp $camp): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'workers';
        $date = rescue(fn () => Carbon::parse((string) $request->query('date', today()->toDateString()))->startOfDay(), today(), false)->min(today());
        $workers = Worker::query()->where('camp_id', $camp->id)->with(['country', 'profession'])->orderBy('name_en')->get();

        return view('admin.camps.show', [
            'camp' => $camp,
            'tab' => $tab,
            'date' => $date,
            'workers' => $workers,
            'occupancy' => $workers->count(),
            'attendance' => CampAttendance::query()->where('camp_id', $camp->id)->whereDate('date', $date)->get()->keyBy('worker_id'),
            'movements' => $tab === 'movements'
                ? CampMovement::query()->where(fn (Builder $query) => $query->where('from_camp_id', $camp->id)->orWhere('to_camp_id', $camp->id))
                    ->with(['worker', 'fromCamp', 'toCamp', 'creator'])->latest('moved_at')->latest('id')->paginate(30)->withQueryString()
                : null,
            'movable' => $tab === 'workers' ? $this->camps->movableWorkers($camp)->with('camp')->orderBy('name_en')->get() : collect(),
            'otherCamps' => Camp::query()->active()->where('branch_id', $camp->branch_id)->whereKeyNot($camp->id)->orderBy('name_ar')->get(),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    /**
     * Check a worker in to this camp (or transfer from another one).
     */
    public function checkIn(Request $request, Camp $camp): RedirectResponse
    {
        $validated = $request->validate([
            'worker_id' => ['required', 'integer'],
            'note' => ['required', 'string', 'max:500'],
        ], [], ['worker_id' => __('camps.fields.worker_id'), 'note' => __('camps.fields.note')]);

        $worker = $this->camps->movableWorkers($camp)->findOrFail($validated['worker_id']);

        return $this->attempt(fn () => $this->camps->move($worker, $camp, $validated['note']), $camp, 'camps.flash.checked_in');
    }

    /**
     * Transfer a worker of this camp to another camp, or check her out.
     */
    public function moveOut(Request $request, Camp $camp, Worker $worker): RedirectResponse
    {
        abort_unless((int) $worker->camp_id === $camp->id, 404);

        $validated = $request->validate([
            'to_camp_id' => ['nullable', 'integer', Rule::exists('camps', 'id')->where('branch_id', $camp->branch_id)->whereNull('deleted_at')],
            'note' => ['required', 'string', 'max:500'],
        ], [], ['to_camp_id' => __('camps.fields.to_camp_id'), 'note' => __('camps.fields.note')]);

        $target = filled($validated['to_camp_id'] ?? null) ? Camp::query()->findOrFail($validated['to_camp_id']) : null;

        return $this->attempt(fn () => $this->camps->move($worker, $target, $validated['note']), $camp, $target ? 'camps.flash.transferred' : 'camps.flash.checked_out');
    }

    public function attendance(Request $request, Camp $camp): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['required', 'array'],
            'rows.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'rows.*.note' => ['nullable', 'string', 'max:500'],
        ], [], ['date' => __('camps.fields.date')]);

        $date = Carbon::parse($validated['date'])->startOfDay();

        try {
            $count = $this->camps->recordAttendance($camp, $date, $validated['rows']);
        } catch (CampException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.camps.show', [$camp, 'tab' => 'attendance', 'date' => $date->toDateString()])
            ->with('success', __('camps.flash.attendance', ['count' => $count]));
    }

    /**
     * All movements of the user's branches.
     */
    public function movements(Request $request): View
    {
        $filters = $request->only(['camp_id', 'type', 'from', 'to', 'search']);

        $movements = CampMovement::query()
            ->when($filters['camp_id'] ?? null, fn (Builder $query, string $camp) => $query->where(fn (Builder $q) => $q->where('from_camp_id', $camp)->orWhere('to_camp_id', $camp)))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('moved_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('moved_at', '<=', $to))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->whereHas('worker', fn (Builder $worker) => $worker->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%")))
            ->with(['worker', 'fromCamp', 'toCamp', 'creator'])
            ->latest('moved_at')->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.camps.movements', [
            'movements' => $movements,
            'filters' => $filters,
            'camps' => Camp::query()->orderBy('name_ar')->get(),
            'types' => CampMovementType::cases(),
        ]);
    }

    /**
     * @param  callable(): mixed  $step
     */
    private function attempt(callable $step, Camp $camp, string $message): RedirectResponse
    {
        try {
            $step();
        } catch (CampException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.camps.show', $camp)->with('success', __($message));
    }
}

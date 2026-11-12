<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LookupType;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeWorkerStatusRequest;
use App\Http\Requests\Admin\WorkerRequest;
use App\Models\Agency;
use App\Models\Authority;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\Country;
use App\Models\Lookup;
use App\Models\Profession;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerSalaryChange;
use App\Services\AuditLogger;
use App\Services\SensitiveData;
use App\Services\Workers\InvalidStatusTransition;
use App\Services\Workers\WorkerStatusService;
use App\Support\ProfileTabs;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkerController extends Controller
{
    private const FILTERS = ['search', 'passport', 'iqama', 'group', 'status', 'country_id', 'profession_id', 'agency_id', 'branch_id', 'camp_id', 'sponsor_type', 'rental_type'];

    private const PHOTO_DIRECTORY = 'worker-photos';

    public function __construct(private AuditLogger $audit, private WorkerStatusService $statuses) {}

    public function index(Request $request): View
    {
        $filters = $request->only(self::FILTERS);

        return view('admin.workers.index', [
            'workers' => $this->filtered($filters)->with(['country', 'profession', 'branch'])->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            ...$this->options($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.workers.create', [
            'worker' => new Worker(['salary' => 0, 'experience_years' => 0]),
            ...$this->options($request->user()),
        ]);
    }

    public function store(WorkerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'salary_change_reason', 'initial_status']);

        $worker = DB::transaction(function () use ($request, $data): Worker {
            $worker = new Worker($data);
            $worker->created_by = $request->user()->id;
            $worker->photo_path = $this->storePhoto($request);
            $worker->save();

            $this->audit->log('worker.created', $worker, ['name' => $worker->translations('name')], new: $this->auditable($worker));
            $this->statuses->initialize($worker, WorkerStatus::from($request->input('initial_status', WorkerStatus::CandidateNew->value)));

            return $worker;
        });

        return redirect()->route('admin.workers.show', $worker)->with('success', __('workers.flash.created'));
    }

    public function show(Request $request, Worker $worker): View
    {
        $worker->load(['country', 'profession', 'agency', 'branch', 'camp', 'creator', 'experiences', 'salaryChanges.changer', 'statusHistory.changer']);
        $tabs = ProfileTabs::for('worker', $request->user());

        return view('admin.workers.show', [
            'worker' => $worker,
            'tabs' => $tabs,
            'activeTab' => ProfileTabs::active($tabs, $request->query('tab')),
            'manualTransitions' => $this->statuses->manualTransitions($worker),
            'reviewTransitions' => $this->statuses->reviewTransitions($worker),
            'authorities' => Authority::query()->active()->where('country_id', $worker->country_id)->orderBy('name_'.app()->getLocale())->get(),
            'lookups' => Lookup::query()->whereIn('id', [...($worker->languages ?? []), ...($worker->skills ?? [])])->get()->keyBy('id'),
        ]);
    }

    public function edit(Request $request, Worker $worker): View
    {
        return view('admin.workers.edit', ['worker' => $worker, ...$this->options($request->user())]);
    }

    public function update(WorkerRequest $request, Worker $worker): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'salary_change_reason', 'initial_status']);
        $old = $this->auditable($worker);
        $oldSalary = (float) $worker->salary;

        DB::transaction(function () use ($request, $worker, $data, $old, $oldSalary): void {
            $worker->fill($data);

            if ($request->hasFile('photo')) {
                $previous = $worker->photo_path;
                $worker->photo_path = $this->storePhoto($request);
                $previous && Storage::disk('local')->delete($previous);
            }

            $worker->save();

            if (round($oldSalary, 2) !== round((float) $worker->salary, 2)) {
                WorkerSalaryChange::query()->create([
                    'worker_id' => $worker->id,
                    'old_salary' => $oldSalary,
                    'new_salary' => $worker->salary,
                    'reason' => $request->input('salary_change_reason'),
                    'changed_by' => $request->user()->id,
                    'changed_at' => now(),
                ]);
            }

            $new = $this->auditable($worker);
            $changed = array_keys(array_diff_assoc(array_map('json_encode', $new), array_map('json_encode', $old)));

            if ($changed !== []) {
                $this->audit->log('worker.updated', $worker, ['name' => $worker->translations('name')], array_intersect_key($old, array_flip($changed)), array_intersect_key($new, array_flip($changed)));
            }
        });

        return redirect()->route('admin.workers.show', $worker)->with('success', __('workers.flash.updated'));
    }

    public function changeStatus(ChangeWorkerStatusRequest $request, Worker $worker): RedirectResponse
    {
        try {
            $this->statuses->transition($worker, WorkerStatus::from($request->input('status')), $request->input('reason'));
        } catch (InvalidStatusTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.workers.show', [$worker, 'tab' => 'status_history'])->with('success', __('workers.flash.status_changed'));
    }

    /**
     * Worker photo from the private disk (never a public URL).
     */
    public function photo(Worker $worker): StreamedResponse
    {
        abort_unless($worker->photo_path && Storage::disk('local')->exists($worker->photo_path), 404);

        return Storage::disk('local')->response($worker->photo_path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=3600']);
    }

    /**
     * Excel export of the filtered list, masked like the screen.
     */
    public function export(Request $request, SensitiveData $sensitive): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'workers').'.xlsx';
        $writer = SimpleExcelWriter::create($path);

        $this->filtered($request->only(self::FILTERS))
            ->with(['country', 'profession', 'agency', 'branch', 'camp'])
            ->latest()
            ->lazy(200)
            ->each(function (Worker $worker) use ($writer, $sensitive): void {
                $writer->addRow([
                    __('workers.fields.name_en') => $worker->name_en,
                    __('workers.fields.name_ar') => $worker->name_ar,
                    __('workers.fields.nationality') => $worker->country?->trans('nationality'),
                    __('workers.fields.profession_id') => $worker->profession?->trans('name'),
                    __('workers.fields.agency_id') => $worker->agency?->trans('name'),
                    __('workers.fields.branch_id') => $worker->branch?->trans('name'),
                    __('workers.fields.camp_id') => $worker->camp?->trans('name'),
                    __('workers.fields.passport_no') => $sensitive->display($worker->passport_no, 'workers.view_sensitive', 'passport'),
                    __('workers.fields.passport_expiry') => $worker->passport_expiry?->format('Y-m-d'),
                    __('workers.fields.iqama_no') => $sensitive->display($worker->iqama_no, 'workers.view_sensitive', 'iqama'),
                    __('workers.fields.iqama_expiry') => $worker->iqama_expiry?->format('Y-m-d'),
                    __('workers.fields.sponsor_type') => $worker->sponsor_type->label(),
                    __('workers.fields.rental_type') => $worker->rental_type->label(),
                    __('workers.fields.salary') => (float) $worker->salary,
                    __('workers.fields.status') => $worker->status->label(),
                ]);
            });

        $writer->close();

        return response()->download($path, 'workers-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * BranchScope limits the query to the user's branches. Passport and iqama
     * searches are exact, through the HMAC lookup columns.
     *
     * @param  array<string, string|null>  $filters
     * @return Builder<Worker>
     */
    private function filtered(array $filters): Builder
    {
        return Worker::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('name_en', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhere('cv_reference_no', 'like', "%{$search}%")
                // Worker number "W-0004" (or just 4).
                ->when(preg_match('/^(?:W-?)?0*(\d+)$/i', trim($search), $number), fn (Builder $byNumber) => $byNumber->orWhereKey((int) $number[1]))))
            ->when($filters['passport'] ?? null, fn (Builder $query, string $passport) => $query->whereIdentifier('passport', $passport))
            ->when($filters['iqama'] ?? null, fn (Builder $query, string $iqama) => $query->whereIdentifier('iqama', $iqama))
            ->when($filters['group'] ?? null, fn (Builder $query, string $group) => $query->whereIn(
                'status',
                array_map(fn (WorkerStatus $status): string => $status->value, WorkerStatus::grouped()[$group] ?? []),
            ))
            ->tap(function (Builder $query) use ($filters): void {
                foreach (['status', 'country_id', 'profession_id', 'agency_id', 'branch_id', 'camp_id', 'sponsor_type', 'rental_type'] as $column) {
                    if (filled($filters[$column] ?? null)) {
                        $query->where($column, $filters[$column]);
                    }
                }
            });
    }

    /**
     * Form and filter options (branches and camps limited to the user's branches).
     *
     * @return array<string, mixed>
     */
    private function options(User $user): array
    {
        return [
            'countries' => Country::query()->ordered()->get(),
            'professions' => Profession::query()->ordered()->get(),
            'agencies' => Agency::query()->orderBy('name_'.app()->getLocale())->get(),
            'branches' => Branch::query()->whereKey($user->allowedBranchIds())->orderBy('code')->get(),
            'camps' => Camp::query()->orderBy('name_'.app()->getLocale())->get(),
            'languages' => Lookup::query()->ofType(LookupType::Language)->active()->ordered()->get(),
            'skills' => Lookup::query()->ofType(LookupType::Skill)->active()->ordered()->get(),
            'statuses' => WorkerStatus::grouped(),
            'sponsorTypes' => SponsorType::cases(),
            'rentalTypes' => WorkerRentalType::cases(),
        ];
    }

    private function storePhoto(Request $request): ?string
    {
        return $request->hasFile('photo') ? $request->file('photo')->store(self::PHOTO_DIRECTORY, 'local') : null;
    }

    /**
     * Audited fields; identifiers are never written to the audit log in clear.
     *
     * @return array<string, mixed>
     */
    private function auditable(Worker $worker): array
    {
        $values = $worker->only([
            'name_ar', 'name_en', 'gender', 'birth_date', 'religion', 'marital_status', 'country_id', 'profession_id',
            'agency_id', 'branch_id', 'camp_id', 'passport_type', 'passport_expiry', 'iqama_expiry', 'medical_expiry',
            'languages', 'experience_years', 'skills', 'salary', 'sponsor_type', 'rental_type', 'musaned_cv_no', 'photo_path',
        ]);

        $values['passport_no'] = $worker->passport_lookup ? substr($worker->passport_lookup, 0, 8) : null;
        $values['iqama_no'] = $worker->iqama_lookup ? substr($worker->iqama_lookup, 0, 8) : null;

        return collect($values)->map(fn ($value) => match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            default => $value,
        })->all();
    }
}

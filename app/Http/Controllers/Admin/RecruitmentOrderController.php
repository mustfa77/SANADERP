<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryMethod;
use App\Enums\LookupType;
use App\Enums\RecruitmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecruitmentOrderRequest;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Country;
use App\Models\Lookup;
use App\Models\Profession;
use App\Models\RecruitmentOrder;
use App\Models\RecruitmentOrderStage;
use App\Models\Setting;
use App\Models\Worker;
use App\Services\Clients\ClientRegistrar;
use App\Services\Recruitment\RecruitmentException;
use App\Services\Recruitment\RecruitmentHandoverPdf;
use App\Services\Recruitment\RecruitmentService;
use App\Services\Workers\InvalidStatusTransition;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Recruitment orders. Every workflow step goes through RecruitmentService.
 */
class RecruitmentOrderController extends Controller
{
    private const FILTERS = ['search', 'status', 'country_id', 'profession_id', 'branch_id'];

    public function __construct(private RecruitmentService $recruitment) {}

    public function index(Request $request): View
    {
        $filters = $request->only(self::FILTERS);

        return view('admin.recruitment.index', [
            'orders' => $this->filtered($filters)->with(['client', 'worker', 'country', 'profession', 'branch'])->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statuses' => RecruitmentStatus::cases(),
            'countries' => Country::query()->ordered()->get(),
            'professions' => Profession::query()->ordered()->get(),
            'branches' => Branch::query()->whereKey($request->user()->allowedBranchIds())->orderBy('code')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        return view('admin.recruitment.create', [
            'clients' => Client::query()->active()->orderBy('name_ar')->get(),
            'countries' => Country::query()->active()->ordered()->get(),
            'professions' => Profession::query()->active()->ordered()->get(),
            'branches' => Branch::query()->whereKey($user->allowedBranchIds())->where('is_active', true)->orderBy('code')->get(),
            'candidates' => $this->recruitment->selectableWorkers()->with(['country', 'profession'])->orderBy('name_en')->limit(500)->get(),
            'selectedClient' => $request->integer('client') ?: null,
            'selectedWorker' => $request->integer('worker') ?: null,
            'canCreateClient' => $user->can('create', Client::class),
        ]);
    }

    /**
     * One step: the client (existing, or new from the same form), the order
     * and, if chosen, the worker already offered to the client. Any failure
     * rolls everything back and keeps the form input.
     */
    public function store(RecruitmentOrderRequest $request, ClientRegistrar $registrar): RedirectResponse
    {
        try {
            $order = DB::transaction(function () use ($request, $registrar): RecruitmentOrder {
                $client = $request->isNewClient()
                    ? $registrar->register($request->newClientData(), $request->user())
                    : Client::query()->findOrFail($request->integer('client_id'));

                $order = $this->recruitment->create([
                    ...$request->safe()->only(['country_id', 'profession_id', 'contract_amount', 'notes']),
                    'client_id' => $client->id,
                    'branch_id' => $client->branch_id,
                ]);

                if ($request->filled('worker_id')) {
                    $order = $this->recruitment->selectCandidate($order, Worker::query()->findOrFail($request->integer('worker_id')));
                }

                return $order;
            });
        } catch (RecruitmentException|InvalidStatusTransition $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.recruitment.show', $order)->with('success', __('recruitment.flash.created', ['number' => $order->number]));
    }

    public function show(RecruitmentOrder $order): View
    {
        $order->load(['client', 'worker.country', 'country', 'profession', 'branch', 'stages.completer', 'cancelReason', 'returnReason', 'arrivalCamp', 'deliverer', 'creator']);

        return view('admin.recruitment.show', [
            'order' => $order,
            'candidates' => $order->status === RecruitmentStatus::New
                ? $this->recruitment->candidatesFor($order)->with('profession')->orderBy('name_en')->limit(100)->get()
                : collect(),
            'cancelReasons' => Lookup::query()->ofType(LookupType::CancelReason)->active()->ordered()->get(),
            'returnReasons' => $order->status === RecruitmentStatus::Delivered
                ? Lookup::query()->ofType(LookupType::ReturnReason)->active()->ordered()->get()
                : collect(),
            // Housings of the order's branch (arrival step), with occupancy.
            'camps' => $order->status === RecruitmentStatus::InTravel
                ? Camp::query()->withoutGlobalScopes()->where('branch_id', $order->branch_id)->active()->orderBy('name_ar')->get()
                    ->each(fn (Camp $camp) => $camp->setAttribute('occupancy', $this->recruitment->campOccupancy($camp)))
                : collect(),
            'quotes' => $order->status === RecruitmentStatus::Arrived
                ? collect(DeliveryMethod::cases())->mapWithKeys(fn (DeliveryMethod $method): array => [$method->value => $this->recruitment->deliveryQuote($order, today()->toDateString(), $method)])->all()
                : [],
            'feeSettings' => [
                'camp_free_days' => Setting::integer('camp_free_days'),
                'camp_daily_fee' => Setting::integer('camp_daily_fee'),
                'home_delivery_fee' => Setting::integer('home_delivery_fee'),
            ],
        ]);
    }

    /**
     * Handover report: estimated fees before delivery (?method=home|office),
     * recorded values afterwards.
     */
    public function handover(Request $request, RecruitmentOrder $order, RecruitmentHandoverPdf $pdf): Response
    {
        abort_unless($order->worker_id && ($order->status->hasArrived()), 404);

        $method = DeliveryMethod::tryFrom((string) $request->query('method')) ?? DeliveryMethod::Office;

        return response($pdf->render($order, $method), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="handover-'.$order->number.'.pdf"',
        ]);
    }

    public function selectCandidate(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $validated = $request->validate(['worker_id' => ['required', 'integer']], [], ['worker_id' => __('recruitment.fields.worker_id')]);
        $worker = Worker::query()->findOrFail($validated['worker_id']);

        return $this->attempt(fn () => $this->recruitment->selectCandidate($order, $worker), 'recruitment.flash.candidate_selected');
    }

    public function releaseCandidate(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']], [], ['reason' => __('recruitment.fields.reason')]);

        return $this->attempt(fn () => $this->recruitment->releaseCandidate($order, $validated['reason']), 'recruitment.flash.candidate_released');
    }

    public function contract(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $request->merge(['visa_number' => strtoupper(trim((string) $request->input('visa_number')))]);

        $validated = $request->validate([
            'visa_number' => ['required', 'string', 'max:30', 'regex:/^[0-9A-Z-]+$/', Rule::unique('recruitment_orders', 'visa_number')->ignore($order)],
            'visa_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'musaned_contract_no' => ['required', 'string', 'max:50'],
            'contracted_at' => ['required', 'date', 'before_or_equal:today'],
            'contract_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ], [], collect(['visa_number', 'visa_issued_at', 'musaned_contract_no', 'contracted_at', 'contract_amount'])
            ->mapWithKeys(fn (string $field): array => [$field => __("recruitment.fields.{$field}")])->all());

        return $this->attempt(fn () => $this->recruitment->contract($order, $validated), 'recruitment.flash.contracted');
    }

    public function startTravel(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $validated = $request->validate(['date' => ['required', 'date', 'before_or_equal:today']], [], ['date' => __('recruitment.fields.travel_started_at')]);

        return $this->attempt(fn () => $this->recruitment->startTravel($order, $validated['date']), 'recruitment.flash.travel_started');
    }

    public function completeStage(Request $request, RecruitmentOrder $order, RecruitmentOrderStage $stage): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['date' => __('recruitment.fields.completed_at'), 'notes' => __('recruitment.fields.notes')]);

        return $this->attempt(fn () => $this->recruitment->completeStage($order, $stage, $validated['date'], $validated['notes'] ?? null), 'recruitment.flash.stage_completed');
    }

    public function arrived(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'camp_id' => ['nullable', 'integer'],
        ], [], ['date' => __('recruitment.fields.arrived_at'), 'camp_id' => __('recruitment.fields.arrival_camp_id')]);

        // Only a housing of the order's branch (checked again by the service).
        $camp = filled($validated['camp_id'] ?? null)
            ? Camp::query()->withoutGlobalScopes()->where('branch_id', $order->branch_id)->findOrFail($validated['camp_id'])
            : null;

        return $this->attempt(fn () => $this->recruitment->markArrived($order, $validated['date'], $camp), 'recruitment.flash.arrived');
    }

    public function deliver(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $fields = ['delivered_at', 'delivery_method', 'receiver_name', 'receiver_phone', 'camp_fee', 'delivery_fee', 'delivery_notes'];

        $validated = $request->validate([
            'delivered_at' => ['required', 'date', 'before_or_equal:today'],
            'delivery_method' => ['required', Rule::enum(DeliveryMethod::class)],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s-]+$/'],
            'camp_fee' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ], [], collect($fields)->mapWithKeys(fn (string $field): array => [$field => __("recruitment.fields.{$field}")])->all());

        return $this->attempt(fn () => $this->recruitment->deliver($order, $validated), 'recruitment.flash.delivered');
    }

    public function completeProbation(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $validated = $request->validate(['date' => ['required', 'date', 'before_or_equal:today']], [], ['date' => __('recruitment.fields.completed_at_probation')]);

        return $this->attempt(fn () => $this->recruitment->completeProbation($order, $validated['date']), 'recruitment.flash.completed');
    }

    public function returnDuringProbation(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'return_reason_id' => ['required', Rule::exists('lookups', 'id')->where('type', LookupType::ReturnReason->value)->whereNull('deleted_at')],
            'outcome' => ['required', Rule::in(['returned', 'absconded'])],
            'return_note' => ['nullable', 'string', 'max:1000'],
        ], [], collect(['return_reason_id', 'return_note', 'outcome'])->mapWithKeys(fn (string $field): array => [$field => __("recruitment.fields.{$field}")])->all()
            + ['date' => __('recruitment.fields.returned_at')]);

        $reason = Lookup::query()->findOrFail($validated['return_reason_id']);

        return $this->attempt(
            fn () => $this->recruitment->returnDuringProbation($order, $validated['date'], $reason, $validated['outcome'] === 'absconded', $validated['return_note'] ?? null),
            'recruitment.flash.returned',
        );
    }

    public function cancel(Request $request, RecruitmentOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason_id' => ['required', Rule::exists('lookups', 'id')->where('type', LookupType::CancelReason->value)->whereNull('deleted_at')],
            'cancel_note' => ['nullable', 'string', 'max:500'],
        ], [], ['cancel_reason_id' => __('recruitment.fields.cancel_reason_id'), 'cancel_note' => __('recruitment.fields.cancel_note')]);

        $reason = Lookup::query()->findOrFail($validated['cancel_reason_id']);

        return $this->attempt(fn () => $this->recruitment->cancel($order, $reason, $validated['cancel_note'] ?? null), 'recruitment.flash.cancelled');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'recruitment').'.xlsx';
        $writer = SimpleExcelWriter::create($path);

        $this->filtered($request->only(self::FILTERS))
            ->with(['client', 'worker', 'country', 'profession', 'branch'])
            ->latest()
            ->lazy(200)
            ->each(function (RecruitmentOrder $order) use ($writer): void {
                $writer->addRow([
                    __('recruitment.fields.number') => $order->number,
                    __('recruitment.fields.client_id') => $order->client?->trans('name'),
                    __('recruitment.fields.country_id') => $order->country?->trans('name'),
                    __('recruitment.fields.profession_id') => $order->profession?->trans('name'),
                    __('recruitment.fields.worker_id') => $order->worker?->trans('name'),
                    __('recruitment.fields.visa_number') => $order->visa_number,
                    __('recruitment.fields.musaned_contract_no') => $order->musaned_contract_no,
                    __('recruitment.fields.contracted_at') => $order->contracted_at?->format('Y-m-d'),
                    __('recruitment.fields.arrived_at') => $order->arrived_at?->format('Y-m-d'),
                    __('recruitment.fields.branch_id') => $order->branch?->trans('name'),
                    __('recruitment.fields.status') => $order->status->label(),
                ]);
            });

        $writer->close();

        return response()->download($path, 'recruitment-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * Run a workflow step; a rule violation becomes an error flash.
     *
     * @param  Closure(): RecruitmentOrder  $step
     */
    private function attempt(Closure $step, string $message): RedirectResponse
    {
        try {
            $order = $step();
        } catch (RecruitmentException|InvalidStatusTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.recruitment.show', $order)->with('success', __($message));
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return Builder<RecruitmentOrder>
     */
    private function filtered(array $filters): Builder
    {
        return RecruitmentOrder::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('number', 'like', "%{$search}%")
                ->orWhere('musaned_contract_no', 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $client) => $client->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"))
                ->orWhereHas('worker', fn (Builder $worker) => $worker->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"))))
            ->tap(function (Builder $query) use ($filters): void {
                foreach (['status', 'country_id', 'profession_id', 'branch_id'] as $column) {
                    if (filled($filters[$column] ?? null)) {
                        $query->where($column, $filters[$column]);
                    }
                }
            });
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LookupType;
use App\Enums\RentalContractStatus;
use App\Enums\RentalType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Lookup;
use App\Models\RentalContract;
use App\Models\Setting;
use App\Models\Worker;
use App\Services\Rental\RentalContractService;
use App\Services\Rental\RentalException;
use App\Services\Rental\RentalPricing;
use App\Services\Workers\InvalidStatusTransition;
use App\Support\PdfDocument;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Monthly rental contracts. Every step goes through RentalContractService.
 */
class RentalContractController extends Controller
{
    public function __construct(private RentalContractService $contracts) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'branch_id', 'ending']);

        $contracts = ($filters['ending'] ?? null) ? $this->contracts->endingSoon() : RentalContract::query();

        $contracts = $contracts
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('number', 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $client) => $client->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                ->orWhereHas('worker', fn (Builder $worker) => $worker->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['branch_id'] ?? null, fn (Builder $query, string $branch) => $query->where('branch_id', $branch))
            ->with(['client', 'worker', 'branch'])
            ->withPaymentTotals()
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderBy('ends_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.rental.index', [
            'contracts' => $contracts,
            'filters' => $filters,
            'statuses' => RentalContractStatus::cases(),
            'branches' => Branch::query()->whereKey($request->user()->allowedBranchIds())->orderBy('code')->get(),
            'endingCount' => $this->contracts->endingSoon()->count(),
            'alertDays' => Setting::integer('days_before_contract_end'),
        ]);
    }

    public function create(Request $request, RentalPricing $pricing): View
    {
        $workers = $this->contracts->eligibleWorkers()->with(['country', 'profession', 'branch'])->orderBy('name_en')->limit(500)->get();
        $prices = collect($workers->pluck('country_id')->unique())
            ->mapWithKeys(fn ($countryId): array => [$countryId => $pricing->activePrice($countryId, RentalType::Monthly)]);

        return view('admin.rental.create', [
            'clients' => Client::query()->active()->orderBy('name_ar')->get(),
            'workers' => $workers,
            'prices' => $prices,
            'selectedClient' => $request->integer('client') ?: null,
            'selectedWorker' => $request->integer('worker') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->whereIn('branch_id', $request->user()->allowedBranchIds())],
            'worker_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date', 'after_or_equal:'.today()->subDays(30)->toDateString()],
            'departed_at' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], $this->attributes(['client_id', 'worker_id', 'starts_at', 'departed_at', 'price', 'discount', 'notes']));

        Worker::query()->findOrFail($validated['worker_id']);

        try {
            $contract = $this->contracts->create($validated);
        } catch (RentalException|InvalidStatusTransition $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.rental.show', $contract)->with('success', __('rental.flash.created', ['number' => $contract->number]));
    }

    public function show(RentalContract $contract): View
    {
        $contract->load([
            'client', 'worker.country', 'worker.profession', 'branch', 'renewedFrom', 'renewal', 'closeReason', 'returnCamp', 'creator',
            'payments.method', 'payments.receiver', 'payments.verifier', 'refunds.requester', 'refunds.decider',
            'assignments.worker', 'assignments.returnReason', 'assignments.returnCamp',
        ]);
        $isActive = $contract->status === RentalContractStatus::Active;

        return view('admin.rental.show', [
            'contract' => $contract,
            'closeReasons' => Lookup::query()->ofType(LookupType::ReturnReason)->active()->ordered()->get(),
            'camps' => Camp::query()->withoutGlobalScopes()->where('branch_id', $contract->branch_id)->active()->orderBy('name_ar')->get(),
            'renewalQuote' => $isActive ? $this->quoteOrNull($contract) : null,
            'replacements' => $isActive
                ? $this->contracts->eligibleWorkers()->where('branch_id', $contract->branch_id)->with(['country', 'profession'])->orderBy('name_en')->get()
                : collect(),
            // Inputs of the return calculation in the page (same rule as RentalContractService::returnQuote).
            'returnRule' => [
                'starts' => $contract->starts_at->toDateString(),
                'ends' => $contract->ends_at->toDateString(),
                'days' => $contract->contractDays(),
                'total' => (float) $contract->total,
                'paid' => $contract->paidAmount(),
                'cutoff' => (int) config('rental.return_cutoff_hour', 17),
            ],
        ]);
    }

    public function renew(Request $request, RentalContract $contract): RedirectResponse
    {
        $validated = $request->validate([
            'price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ], [], $this->attributes(['price', 'discount']));

        return $this->attempt(fn () => $this->contracts->renew($contract, $validated), 'rental.flash.renewed');
    }

    /**
     * Worker returned (end of contract or early return).
     */
    public function close(Request $request, RentalContract $contract): RedirectResponse
    {
        $validated = $this->validateReturn($request, reasonRequired: false, extra: [
            'charge' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'refund' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        return $this->attempt(
            fn () => $this->contracts->close($contract, $validated['returned_at'], $this->reason($validated), $validated['close_note'] ?? null, $this->camp($contract, $validated), $validated['charge'] ?? null, $validated['refund'] ?? null),
            'rental.flash.closed',
        );
    }

    public function cancel(Request $request, RentalContract $contract): RedirectResponse
    {
        $validated = $this->validateReturn($request, reasonRequired: true, extra: [
            'refund' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        return $this->attempt(
            fn () => $this->contracts->cancel($contract, $validated['returned_at'], $this->reason($validated), $validated['close_note'] ?? null, $this->camp($contract, $validated), $validated['refund'] ?? null),
            'rental.flash.cancelled',
        );
    }

    /**
     * Replace the worker on the same contract.
     */
    public function replace(Request $request, RentalContract $contract): RedirectResponse
    {
        $validated = $this->validateReturn($request, reasonRequired: true, extra: [
            'new_worker_id' => ['required', 'integer'],
            'departed_at' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateTimeString()],
        ]);
        $newWorker = Worker::query()->findOrFail($validated['new_worker_id']);

        return $this->attempt(
            fn () => $this->contracts->replace($contract, $newWorker, $validated['returned_at'], $validated['departed_at'], $this->reason($validated), $validated['close_note'] ?? null, $this->camp($contract, $validated)),
            'rental.flash.replaced',
        );
    }

    /**
     * @param  array<string, array<int, mixed>>  $extra
     * @return array<string, mixed>
     */
    private function validateReturn(Request $request, bool $reasonRequired, array $extra = []): array
    {
        return $request->validate([
            'returned_at' => ['required', 'date', 'before_or_equal:'.now()->addMinutes(5)->toDateTimeString()],
            'close_reason_id' => [$reasonRequired ? 'required' : 'nullable', Rule::exists('lookups', 'id')->where('type', LookupType::ReturnReason->value)->whereNull('deleted_at')],
            'close_note' => ['nullable', 'string', 'max:1000'],
            'camp_id' => ['nullable', 'integer'],
            ...$extra,
        ], [], $this->attributes(['returned_at', 'close_reason_id', 'close_note', 'camp_id', 'charge', 'refund', 'new_worker_id', 'departed_at']));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function reason(array $validated): ?Lookup
    {
        return filled($validated['close_reason_id'] ?? null) ? Lookup::query()->find($validated['close_reason_id']) : null;
    }

    /**
     * Only a housing of the contract's branch.
     *
     * @param  array<string, mixed>  $validated
     */
    private function camp(RentalContract $contract, array $validated): ?Camp
    {
        return filled($validated['camp_id'] ?? null)
            ? Camp::query()->withoutGlobalScopes()->where('branch_id', $contract->branch_id)->findOrFail($validated['camp_id'])
            : null;
    }

    /**
     * Printable contract (PDF, opens in the browser).
     */
    public function pdf(RentalContract $contract): Response
    {
        $contract->load(['client', 'worker.country', 'worker.profession', 'branch', 'creator']);

        return response(PdfDocument::render('rental.contract-pdf', ['contract' => $contract], $contract->number), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$contract->number.'.pdf"',
        ]);
    }

    /**
     * @return array{price: float, discount: float, vat_percent: float, vat_amount: float, total: float}|null
     */
    private function quoteOrNull(RentalContract $contract): ?array
    {
        try {
            return $this->contracts->priceFor($contract->worker, null, null);
        } catch (RentalException) {
            return null;
        }
    }

    /**
     * @param  Closure(): RentalContract  $step
     */
    private function attempt(Closure $step, string $message): RedirectResponse
    {
        try {
            $contract = $step();
        } catch (RentalException|InvalidStatusTransition $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.rental.show', $contract)->with('success', __($message));
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, string>
     */
    private function attributes(array $fields): array
    {
        return collect($fields)->mapWithKeys(fn (string $field): array => [$field => __("rental.fields.{$field}")])->all();
    }
}

<?php

namespace App\Services\Rental;

use App\Enums\RentalContractStatus;
use App\Enums\RentalType;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Lookup;
use App\Models\RentalContract;
use App\Models\RentalContractWorker;
use App\Models\Setting;
use App\Models\Worker;
use App\Services\AuditLogger;
use App\Services\Workers\WorkerStatusService;
use App\Support\SequenceNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Monthly rental contracts. A contract covers one month; renewing creates
 * the next month's contract. The worker's status follows through
 * WorkerStatusService (related = the contract):
 *
 *   create   worker available -> rented_monthly (leaves the camp)
 *   renew    contract active -> renewed, new active contract (worker stays rented)
 *   close    contract active -> ended (on/after the end date) or terminated
 *            (before it, with a reason: used days charged, refund requested);
 *            worker -> available (back to a camp)
 *   cancel   contract active -> cancelled (nothing charged, paid amount refunded)
 *   replace  same contract, the worker comes back and another goes out
 *
 * Every departure / return is kept with its date and time (rental_contract_workers).
 */
class RentalContractService
{
    public function __construct(
        private WorkerStatusService $statuses,
        private AuditLogger $audit,
        private RentalPricing $pricing,
        private RefundService $refunds,
    ) {}

    /**
     * Workers that can be rented monthly now: available, company
     * sponsorship and marked for monthly rental.
     *
     * @return Builder<Worker>
     */
    public function eligibleWorkers(): Builder
    {
        return Worker::query()
            ->where('status', WorkerStatus::Available)
            ->where('sponsor_type', SponsorType::Company)
            ->where('rental_type', WorkerRentalType::Monthly);
    }

    /**
     * The contract period: one month from the start date.
     */
    public function endDate(Carbon $start): Carbon
    {
        return $start->copy()->addMonthNoOverflow()->subDay();
    }

    /**
     * @param  array{client_id: int, worker_id: int, starts_at: string, departed_at?: ?string, price?: float|string|null, discount?: float|string|null, notes?: ?string}  $data
     */
    public function create(array $data): RentalContract
    {
        return DB::transaction(function () use ($data): RentalContract {
            $client = Client::query()->findOrFail($data['client_id']);
            $worker = Worker::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($data['worker_id']);

            if (! $this->eligibleWorkers()->withoutGlobalScopes()->whereKey($worker->getKey())->exists()) {
                throw RentalException::because('worker_not_available');
            }

            if ($worker->branch_id !== $client->branch_id) {
                throw RentalException::because('branch_mismatch');
            }

            $start = Carbon::parse($data['starts_at'])->startOfDay();
            $contract = new RentalContract(['notes' => $data['notes'] ?? null]);
            $contract->forceFill([
                'number' => SequenceNumber::next(RentalContract::class, 'MR'),
                'branch_id' => $client->branch_id,
                'client_id' => $client->getKey(),
                'worker_id' => $worker->getKey(),
                'starts_at' => $start,
                'ends_at' => $this->endDate($start),
                'created_by' => Auth::id(),
                ...$this->priceFor($worker, $data['price'] ?? null, $data['discount'] ?? null),
            ])->save();

            $departedAt = filled($data['departed_at'] ?? null) ? Carbon::parse($data['departed_at']) : $this->defaultDeparture($start);
            $this->sendOut($contract, $worker, $departedAt);

            $this->audit->log('rental.created', $contract, $this->params($contract), new: [
                ...$contract->only(['number', 'starts_at', 'ends_at', 'total']),
                'departed_at' => $departedAt->toDateTimeString(),
            ]);

            return $contract;
        });
    }

    /**
     * Departure time when none is given: now if the contract starts today,
     * otherwise 08:00 on the start date.
     */
    private function defaultDeparture(Carbon $start): Carbon
    {
        return $start->isToday() ? now()->startOfMinute() : $start->copy()->setTime(8, 0);
    }

    /**
     * The worker leaves the office to the client: rented_monthly, out of the
     * housing, and a new period on the contract.
     */
    private function sendOut(RentalContract $contract, Worker $worker, Carbon $departedAt, bool $changeStatus = true): RentalContractWorker
    {
        if ($changeStatus) {
            $this->statuses->transition($worker, WorkerStatus::RentedMonthly, __('rental.reasons.rented', ['number' => $contract->number]), $contract);
        }

        $worker->forceFill(['camp_id' => null])->save();

        $assignment = new RentalContractWorker;
        $assignment->forceFill([
            'rental_contract_id' => $contract->getKey(),
            'worker_id' => $worker->getKey(),
            'departed_at' => $departedAt,
            'created_by' => Auth::id(),
        ])->save();

        return $assignment;
    }

    /**
     * The current worker comes back: available again, optionally in a housing.
     */
    private function bringBack(RentalContract $contract, Carbon $returnedAt, ?Lookup $reason, ?string $note, ?Camp $camp): Worker
    {
        if ($camp && ($camp->branch_id !== $contract->branch_id || ! $camp->is_active)) {
            throw RentalException::because('camp_not_allowed');
        }

        $assignment = $contract->currentAssignment();

        if ($assignment && $returnedAt->lt($assignment->departed_at)) {
            throw RentalException::because('return_before_departure');
        }

        $worker = $contract->worker;
        $this->statuses->transition($worker, WorkerStatus::Available, __('rental.reasons.returned', ['number' => $contract->number]), $contract);
        $worker->forceFill(['camp_id' => $camp?->getKey()])->save();

        $assignment?->forceFill([
            'returned_at' => $returnedAt,
            'return_reason_id' => $reason?->getKey(),
            'return_note' => $note,
            'return_camp_id' => $camp?->getKey(),
        ])->save();

        return $worker;
    }

    /**
     * What the client owes if the worker comes back at $returnedAt: the used
     * days (the return day is free before the cut-off hour, 17:00), their
     * share of the total, and the suggested refund of the net amount paid.
     *
     * @return array{early: bool, contract_days: int, used_days: int, charge: float, paid: float, refund: float}
     */
    public function returnQuote(RentalContract $contract, Carbon $returnedAt): array
    {
        $days = $contract->contractDays();
        $early = $returnedAt->copy()->startOfDay()->lt($contract->ends_at);

        if (! $early) {
            $used = $days;
        } else {
            $lastDay = $returnedAt->hour < (int) config('rental.return_cutoff_hour', 17) ? $returnedAt->copy()->subDay() : $returnedAt->copy();
            $used = $lastDay->startOfDay()->lt($contract->starts_at) ? 0 : (int) $contract->starts_at->diffInDays($lastDay->startOfDay()) + 1;
            $used = min($days, $used);
        }

        $charge = $early ? round((float) $contract->total * $used / $days, 2) : (float) $contract->total;
        $paid = $contract->paidAmount();

        return [
            'early' => $early,
            'contract_days' => $days,
            'used_days' => $used,
            'charge' => $charge,
            'paid' => $paid,
            'refund' => max(0, round($paid - $charge, 2)),
        ];
    }

    /**
     * Replace the worker: the current one comes back, another eligible
     * worker goes out on the same contract (same price, same end date).
     */
    public function replace(RentalContract $contract, Worker $newWorker, string $returnedAt, string $departedAt, Lookup $reason, ?string $note = null, ?Camp $camp = null): RentalContract
    {
        return DB::transaction(function () use ($contract, $newWorker, $returnedAt, $departedAt, $reason, $note, $camp): RentalContract {
            $contract = $this->lock($contract);
            $newWorker = Worker::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($newWorker->getKey());
            $back = Carbon::parse($returnedAt);
            $out = Carbon::parse($departedAt);

            if ($newWorker->is($contract->worker) || ! $this->eligibleWorkers()->withoutGlobalScopes()->whereKey($newWorker->getKey())->exists()) {
                throw RentalException::because('worker_not_available');
            }

            if ($newWorker->branch_id !== $contract->branch_id) {
                throw RentalException::because('branch_mismatch');
            }

            if ($out->lt($back->copy()->startOfDay())) {
                throw RentalException::because('replacement_before_return');
            }

            $old = $this->bringBack($contract, $back, $reason, $note, $camp);
            $this->sendOut($contract, $newWorker, $out);
            $contract->forceFill(['worker_id' => $newWorker->getKey()])->save();
            $contract->setRelation('worker', $newWorker);

            $this->audit->log('rental.replaced', $contract, [...$this->params($contract), 'old' => $old->translations('name'), 'new' => $newWorker->translations('name')], new: [
                'returned_at' => $back->toDateTimeString(),
                'departed_at' => $out->toDateTimeString(),
                'reason' => $reason->name_en,
            ]);

            return $contract;
        });
    }

    /**
     * Next month's contract for the same client and worker. The current
     * contract is closed as "renewed".
     *
     * @param  array{price?: float|string|null, discount?: float|string|null, notes?: ?string}  $data
     */
    public function renew(RentalContract $contract, array $data = []): RentalContract
    {
        return DB::transaction(function () use ($contract, $data): RentalContract {
            $contract = $this->lock($contract);
            $start = $contract->ends_at->copy()->addDay()->startOfDay();

            $renewal = new RentalContract(['notes' => $data['notes'] ?? $contract->notes]);
            $renewal->forceFill([
                'number' => SequenceNumber::next(RentalContract::class, 'MR'),
                'branch_id' => $contract->branch_id,
                'client_id' => $contract->client_id,
                'worker_id' => $contract->worker_id,
                'renewed_from_id' => $contract->getKey(),
                'starts_at' => $start,
                'ends_at' => $this->endDate($start),
                'created_by' => Auth::id(),
                ...$this->priceFor($contract->worker, $data['price'] ?? null, $data['discount'] ?? null),
            ])->save();

            $this->setStatus($contract, RentalContractStatus::Renewed);

            // The worker stays with the client: her period moves to the new contract.
            $contract->currentAssignment()?->forceFill(['returned_at' => $start, 'return_note' => __('rental.renewed_note', ['number' => $renewal->number])])->save();
            $this->sendOut($renewal, $contract->worker, $start->copy(), changeStatus: false);

            $this->audit->log('rental.renewed', $renewal, [...$this->params($renewal), 'previous' => $contract->number], new: $renewal->only(['starts_at', 'ends_at', 'total']));

            return $renewal;
        });
    }

    /**
     * The worker comes back at $returnedAt (date and time). Before the end
     * date it is an early termination: a reason is needed, the amount due
     * becomes the used days ($charge overrides the calculation) and a refund
     * request is made for $refund (default: net paid − amount due).
     */
    public function close(RentalContract $contract, string $returnedAt, ?Lookup $reason = null, ?string $note = null, ?Camp $camp = null, float|string|null $charge = null, float|string|null $refund = null): RentalContract
    {
        return DB::transaction(function () use ($contract, $returnedAt, $reason, $note, $camp, $charge, $refund): RentalContract {
            $contract = $this->lock($contract);
            $back = Carbon::parse($returnedAt);

            if ($back->copy()->startOfDay()->lt($contract->starts_at)) {
                throw RentalException::because('close_before_start');
            }

            $quote = $this->returnQuote($contract, $back);

            if ($quote['early'] && ! $reason) {
                throw RentalException::because('reason_required');
            }

            $this->bringBack($contract, $back, $reason, $note, $camp);

            $contract->forceFill([
                'closed_at' => $back->copy()->startOfDay(),
                'close_reason_id' => $reason?->getKey(),
                'close_note' => $note,
                'return_camp_id' => $camp?->getKey(),
            ]);

            if ($quote['early']) {
                $due = filled($charge) ? max(0, min((float) $contract->total, round((float) $charge, 2))) : $quote['charge'];
                $contract->final_total = $due;
            }

            $this->setStatus($contract, $quote['early'] ? RentalContractStatus::Terminated : RentalContractStatus::Ended);
            $this->requestRefund($contract, $quote['early'] ? $refund : 0, __('rental.refund_reasons.early', ['number' => $contract->number]));

            $this->audit->log('rental.closed', $contract, [...$this->params($contract), 'status' => $this->statusParam($contract)], new: [
                'returned_at' => $back->toDateTimeString(),
                'status' => $contract->status->value,
                'used_days' => $quote['used_days'],
                'final_total' => $contract->final_total,
                'reason' => $reason?->name_en,
                'note' => $note,
            ]);

            return $contract;
        });
    }

    /**
     * Cancel the contract: nothing is charged and the net amount paid is
     * refunded ($refund overrides it). The worker comes back.
     */
    public function cancel(RentalContract $contract, string $returnedAt, Lookup $reason, ?string $note = null, ?Camp $camp = null, float|string|null $refund = null): RentalContract
    {
        return DB::transaction(function () use ($contract, $returnedAt, $reason, $note, $camp, $refund): RentalContract {
            $contract = $this->lock($contract);
            $back = Carbon::parse($returnedAt);

            $this->bringBack($contract, $back, $reason, $note, $camp);

            $contract->forceFill([
                'closed_at' => $back->copy()->startOfDay(),
                'close_reason_id' => $reason->getKey(),
                'close_note' => $note,
                'return_camp_id' => $camp?->getKey(),
                'final_total' => 0,
            ]);
            $this->setStatus($contract, RentalContractStatus::Cancelled);
            $this->requestRefund($contract, $refund, __('rental.refund_reasons.cancelled', ['number' => $contract->number]));

            $this->audit->log('rental.closed', $contract, [...$this->params($contract), 'status' => $this->statusParam($contract)], new: [
                'returned_at' => $back->toDateTimeString(),
                'status' => $contract->status->value,
                'reason' => $reason->name_en,
                'note' => $note,
            ]);

            return $contract;
        });
    }

    /**
     * Refund request for what was paid above the amount due ($amount
     * overrides it; 0 = none).
     */
    private function requestRefund(RentalContract $contract, float|string|null $amount, string $reason): void
    {
        $refundable = $this->refunds->refundable($contract);
        $suggested = max(0, round($contract->paidAmount() - $contract->dueTotal(), 2));
        $value = filled($amount) ? round((float) $amount, 2) : min($suggested, $refundable);

        if ($value > 0) {
            $this->refunds->request($contract, $value, $reason);
        }
    }

    /**
     * @return array{ar: string, en: string}
     */
    private function statusParam(RentalContract $contract): array
    {
        return ['ar' => __('rental.statuses.'.$contract->status->value, [], 'ar'), 'en' => __('rental.statuses.'.$contract->status->value, [], 'en')];
    }

    /**
     * Active contracts ending within the "contract end" alert period (or
     * already past their end date).
     *
     * @return Builder<RentalContract>
     */
    public function endingSoon(): Builder
    {
        return RentalContract::query()
            ->where('status', RentalContractStatus::Active)
            ->whereDate('ends_at', '<=', today()->addDays(Setting::integer('days_before_contract_end')));
    }

    /**
     * Price before VAT: the given amount, or the active monthly price of
     * the worker's country.
     *
     * @return array{price: float, discount: float, vat_percent: float, vat_amount: float, total: float}
     */
    public function priceFor(Worker $worker, float|string|null $price, float|string|null $discount): array
    {
        $active = $this->pricing->activePrice($worker->country_id, RentalType::Monthly);

        if (! filled($price) && ! $active) {
            throw RentalException::because('no_price');
        }

        return $this->pricing->amounts(
            filled($price) ? (float) $price : (float) $active->price,
            (float) ($discount ?: 0),
            $active ? (float) $active->vat_percent : 15.0,
        );
    }

    private function lock(RentalContract $contract): RentalContract
    {
        $locked = RentalContract::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($contract->getKey());

        if ($locked->status !== RentalContractStatus::Active) {
            throw RentalException::because('not_active', ['status' => $locked->status->label()]);
        }

        return $locked;
    }

    private function setStatus(RentalContract $contract, RentalContractStatus $status): void
    {
        RentalContract::withStatusChange(fn () => $contract->forceFill(['status' => $status])->save());
    }

    /**
     * @return array<string, mixed>
     */
    private function params(RentalContract $contract): array
    {
        return ['number' => $contract->number, 'client' => $contract->client?->translations('name'), 'worker' => $contract->worker?->translations('name')];
    }
}

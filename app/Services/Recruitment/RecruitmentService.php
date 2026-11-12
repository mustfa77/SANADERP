<?php

namespace App\Services\Recruitment;

use App\Enums\DeliveryMethod;
use App\Enums\RecruitmentStatus;
use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\Camp;
use App\Models\Lookup;
use App\Models\RecruitmentOrder;
use App\Models\RecruitmentOrderStage;
use App\Models\Setting;
use App\Models\Worker;
use App\Services\AuditLogger;
use App\Services\Transport\TripService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Recruitment order workflow. Every step changes the order and, through
 * WorkerStatusService (related = the order), the worker's status:
 *
 *   new -> candidate_selected  worker ready_for_selection -> on_hold
 *   candidate_selected -> new  worker on_hold -> ready_for_selection
 *   -> contracted              worker on_hold -> contracted (visa number recorded)
 *   -> in_travel               worker -> in_travel_process (stages created)
 *   -> arrived                 worker -> arrived (all stages completed, housed in a camp)
 *   -> delivered               worker -> delivered -> in_probation (fees, receiver, leaves the camp)
 *   delivered -> completed     worker -> probation_completed
 *   delivered -> returned      worker -> awaiting_decision, or absconded
 *   new / candidate_selected -> cancelled
 */
class RecruitmentService
{
    public function __construct(private WorkerStatusService $statuses, private AuditLogger $audit, private TripService $trips) {}

    /**
     * @param  array{branch_id: int, client_id: int, country_id: int, profession_id: int, contract_amount?: ?float, notes?: ?string}  $data
     */
    public function create(array $data): RecruitmentOrder
    {
        return DB::transaction(function () use ($data): RecruitmentOrder {
            $order = new RecruitmentOrder($data);
            $order->number = $this->nextNumber();
            $order->created_by = Auth::id();
            $order->save();

            $this->audit->log('recruitment.created', $order, $this->params($order), new: $order->only(['number', 'client_id', 'country_id', 'profession_id', 'branch_id']));

            return $order;
        });
    }

    /**
     * Workers that may be selected for the order: ready for selection,
     * client sponsorship, same country and profession, not in another open order.
     *
     * @return Builder<Worker>
     */
    public function candidatesFor(RecruitmentOrder $order): Builder
    {
        return $this->selectableWorkers()
            ->where('country_id', $order->country_id)
            ->where('profession_id', $order->profession_id);
    }

    /**
     * Workers that can be offered to any client now (new order screen):
     * ready for selection, client sponsorship, not in another open order.
     *
     * @return Builder<Worker>
     */
    public function selectableWorkers(): Builder
    {
        return Worker::query()
            ->where('status', WorkerStatus::ReadyForSelection)
            ->where('sponsor_type', SponsorType::Client)
            ->whereDoesntHave('recruitmentOrders', fn (Builder $orders) => $orders->whereNotIn('status', RecruitmentStatus::closed()));
    }

    public function selectCandidate(RecruitmentOrder $order, Worker $worker): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $worker): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::New);

            if (! $this->candidatesFor($order)->withoutGlobalScopes()->whereKey($worker->getKey())->exists()) {
                throw RecruitmentException::because('candidate_not_eligible');
            }

            $this->statuses->transition($worker, WorkerStatus::OnHold, __('recruitment.reasons.selected', ['number' => $order->number]), $order);
            $order->worker_id = $worker->getKey();
            $this->setStatus($order, RecruitmentStatus::CandidateSelected);

            $this->audit->log('recruitment.candidate_selected', $order, [...$this->params($order), 'worker' => $worker->translations('name')]);

            return $order;
        });
    }

    public function releaseCandidate(RecruitmentOrder $order, string $reason): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $reason): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::CandidateSelected);
            $worker = $order->worker;

            $this->statuses->transition($worker, WorkerStatus::ReadyForSelection, $reason, $order);
            $order->worker_id = null;
            $this->setStatus($order, RecruitmentStatus::New);

            $this->audit->log('recruitment.candidate_released', $order, [...$this->params($order), 'worker' => $worker->translations('name')], new: ['reason' => $reason]);

            return $order;
        });
    }

    /**
     * Record the contract with the order's visa (one visa per order; a client
     * may have any number of orders). The visa number is unique system-wide.
     *
     * @param  array{visa_number: string, visa_issued_at?: ?string, musaned_contract_no: string, contracted_at: string, contract_amount?: ?float}  $data
     */
    public function contract(RecruitmentOrder $order, array $data): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $data): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::CandidateSelected);

            $visaTaken = RecruitmentOrder::query()->withoutGlobalScopes()
                ->where('visa_number', $data['visa_number'])
                ->whereKeyNot($order->getKey())
                ->exists();

            if ($visaTaken) {
                throw RecruitmentException::because('visa_used', ['visa' => $data['visa_number']]);
            }

            $this->statuses->transition($order->worker, WorkerStatus::Contracted, __('recruitment.reasons.contracted', ['number' => $order->number]), $order);

            $order->forceFill([
                'visa_number' => $data['visa_number'],
                'visa_issued_at' => $data['visa_issued_at'] ?? null,
                'musaned_contract_no' => $data['musaned_contract_no'],
                'contracted_at' => $data['contracted_at'],
                'contract_amount' => $data['contract_amount'] ?? $order->contract_amount,
            ]);
            $this->setStatus($order, RecruitmentStatus::Contracted);

            $this->audit->log('recruitment.contracted', $order, $this->params($order), new: [
                'visa_number' => $data['visa_number'],
                'musaned_contract_no' => $data['musaned_contract_no'],
                'contracted_at' => $data['contracted_at'],
            ]);

            return $order;
        });
    }

    /**
     * Start the travel process: copy the country's active stages, the first
     * one starting today.
     */
    public function startTravel(RecruitmentOrder $order, ?string $date = null): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $date): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::Contracted);
            $start = Carbon::parse($date ?? today())->startOfDay();

            $stages = $order->country->travelStages()->active()->get();

            foreach ($stages->values() as $index => $stage) {
                $order->stages()->create([
                    'country_travel_stage_id' => $stage->id,
                    'name_ar' => $stage->name_ar,
                    'name_en' => $stage->name_en,
                    'sort_order' => $index + 1,
                    'max_days' => $stage->max_days,
                    'started_at' => $index === 0 ? $start : null,
                    'due_at' => $index === 0 && $stage->max_days ? $start->copy()->addDays($stage->max_days) : null,
                ]);
            }

            $this->statuses->transition($order->worker, WorkerStatus::InTravelProcess, __('recruitment.reasons.travel_started', ['number' => $order->number]), $order);
            $order->travel_started_at = $start;
            $this->setStatus($order, RecruitmentStatus::InTravel);

            $this->audit->log('recruitment.travel_started', $order, $this->params($order), new: ['stages' => $stages->count()]);

            return $order;
        });
    }

    /**
     * Complete the current stage and start the next one on the same date.
     */
    public function completeStage(RecruitmentOrder $order, RecruitmentOrderStage $stage, string $date, ?string $notes = null): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $stage, $date, $notes): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::InTravel);
            $current = $order->stages()->whereNull('completed_at')->orderBy('sort_order')->first();

            if (! $current || ! $current->is($stage)) {
                throw RecruitmentException::because('stage_not_current');
            }

            $completedAt = Carbon::parse($date)->startOfDay();

            if ($current->started_at && $completedAt->lt($current->started_at)) {
                throw RecruitmentException::because('stage_date_before_start');
            }

            $current->update(['completed_at' => $completedAt, 'notes' => $notes, 'completed_by' => Auth::id()]);

            $next = $order->stages()->whereNull('completed_at')->orderBy('sort_order')->first();
            $next?->update([
                'started_at' => $completedAt,
                'due_at' => $next->max_days ? $completedAt->copy()->addDays($next->max_days) : null,
            ]);

            $this->audit->log('recruitment.stage_completed', $order, [...$this->params($order), 'stage' => $current->translations('name')], new: ['completed_at' => $completedAt->toDateString()]);

            return $order;
        });
    }

    /**
     * The worker has arrived. She stays in one of the order branch's
     * housings ($camp) until delivery, or none when she goes straight to
     * the client.
     */
    public function markArrived(RecruitmentOrder $order, string $date, ?Camp $camp = null): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $date, $camp): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::InTravel);

            if ($order->stages()->whereNull('completed_at')->exists()) {
                throw RecruitmentException::because('stages_pending');
            }

            if ($camp && ($camp->branch_id !== $order->branch_id || ! $camp->is_active)) {
                throw RecruitmentException::because('camp_not_allowed');
            }

            if ($camp && $camp->capacity && $this->campOccupancy($camp) >= $camp->capacity) {
                throw RecruitmentException::because('camp_full', ['camp' => $camp->trans('name')]);
            }

            $worker = $order->worker;
            $this->statuses->transition($worker, WorkerStatus::Arrived, __('recruitment.reasons.arrived', ['number' => $order->number]), $order);
            $worker->forceFill(['camp_id' => $camp?->getKey()])->save();

            $order->forceFill(['arrived_at' => $date, 'arrival_camp_id' => $camp?->getKey()]);
            $this->setStatus($order, RecruitmentStatus::Arrived);

            $this->audit->log('recruitment.arrived', $order, [...$this->params($order), 'camp' => $camp?->translations('name')], new: ['arrived_at' => $date, 'camp_id' => $camp?->getKey()]);

            return $order;
        });
    }

    /**
     * Workers housed in the camp now.
     */
    public function campOccupancy(Camp $camp): int
    {
        return Worker::query()->withoutGlobalScopes()->where('camp_id', $camp->getKey())->count();
    }

    /**
     * Fees for delivering the order on $date: housing days after the free
     * days (only when she stayed in a housing) and the home delivery fee.
     *
     * @return array{camp_days: int, chargeable_days: int, daily_fee: float, camp_fee: float, delivery_fee: float, total: float}
     */
    public function deliveryQuote(RecruitmentOrder $order, string $date, DeliveryMethod $method): array
    {
        $campDays = $order->arrival_camp_id && $order->arrived_at
            ? max(0, (int) $order->arrived_at->copy()->startOfDay()->diffInDays(Carbon::parse($date)->startOfDay(), false))
            : 0;
        $chargeable = max(0, $campDays - Setting::integer('camp_free_days'));
        $dailyFee = (float) Setting::integer('camp_daily_fee');
        $campFee = $chargeable * $dailyFee;
        $deliveryFee = $method === DeliveryMethod::Home ? (float) Setting::integer('home_delivery_fee') : 0.0;

        return [
            'camp_days' => $campDays,
            'chargeable_days' => $chargeable,
            'daily_fee' => $dailyFee,
            'camp_fee' => $campFee,
            'delivery_fee' => $deliveryFee,
            'total' => $campFee + $deliveryFee,
        ];
    }

    /**
     * Hand the worker over to the client. Fees default to the quote and may
     * be changed or waived by the user. The worker leaves the housing and
     * her probation starts (Settings > Recruitment > probation period).
     *
     * @param  array{delivered_at: string, delivery_method: string, receiver_name: string, receiver_phone?: ?string, camp_fee?: float|string|null, delivery_fee?: float|string|null, delivery_notes?: ?string}  $data
     */
    public function deliver(RecruitmentOrder $order, array $data): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $data): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::Arrived);
            $date = Carbon::parse($data['delivered_at'])->startOfDay();

            if ($order->arrived_at && $date->lt($order->arrived_at)) {
                throw RecruitmentException::because('delivery_before_arrival');
            }

            $method = DeliveryMethod::from($data['delivery_method']);
            $quote = $this->deliveryQuote($order, $date->toDateString(), $method);
            $campFee = filled($data['camp_fee'] ?? null) ? (float) $data['camp_fee'] : $quote['camp_fee'];
            $deliveryFee = filled($data['delivery_fee'] ?? null) ? (float) $data['delivery_fee'] : $quote['delivery_fee'];

            $worker = $order->worker;
            $this->statuses->transition($worker, WorkerStatus::Delivered, __('recruitment.reasons.delivered', ['number' => $order->number]), $order);
            $this->statuses->transition($worker, WorkerStatus::InProbation, __('recruitment.reasons.probation_started', ['number' => $order->number]), $order);
            $worker->forceFill(['camp_id' => null])->save();

            $order->forceFill([
                'delivered_at' => $date,
                'delivery_method' => $method,
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'] ?? null,
                'camp_days' => $quote['camp_days'],
                'camp_fee' => $campFee,
                'delivery_fee' => $deliveryFee,
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'delivered_by' => Auth::id(),
                'probation_ends_at' => $date->copy()->addDays(Setting::integer('probation_days')),
            ]);
            $this->setStatus($order, RecruitmentStatus::Delivered);

            if ($method === DeliveryMethod::Home) {
                $this->trips->forHomeDelivery($order);
            }

            $this->audit->log('recruitment.delivered', $order, $this->params($order), new: [
                'delivered_at' => $date->toDateString(),
                'delivery_method' => $method->value,
                'receiver_name' => $data['receiver_name'],
                'camp_days' => $quote['camp_days'],
                'camp_fee' => $campFee,
                'delivery_fee' => $deliveryFee,
                'quoted_total' => $quote['total'],
            ]);

            return $order;
        });
    }

    /**
     * Probation passed: the order is completed.
     */
    public function completeProbation(RecruitmentOrder $order, string $date): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $date): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::Delivered);

            if ($order->delivered_at && Carbon::parse($date)->lt($order->delivered_at)) {
                throw RecruitmentException::because('date_before_delivery');
            }

            $this->statuses->transition($order->worker, WorkerStatus::ProbationCompleted, __('recruitment.reasons.probation_completed', ['number' => $order->number]), $order);
            $order->completed_at = $date;
            $this->setStatus($order, RecruitmentStatus::Completed);

            $this->audit->log('recruitment.completed', $order, $this->params($order), new: ['completed_at' => $date]);

            return $order;
        });
    }

    /**
     * The client returned the worker during probation (she waits for a
     * decision: transfer, deportation...) or she absconded.
     */
    public function returnDuringProbation(RecruitmentOrder $order, string $date, Lookup $reason, bool $absconded = false, ?string $note = null): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $date, $reason, $absconded, $note): RecruitmentOrder {
            $order = $this->lock($order, RecruitmentStatus::Delivered);

            if ($order->delivered_at && Carbon::parse($date)->lt($order->delivered_at)) {
                throw RecruitmentException::because('date_before_delivery');
            }

            $text = __($absconded ? 'recruitment.reasons.absconded' : 'recruitment.reasons.returned', ['number' => $order->number]);
            $this->statuses->transition($order->worker, $absconded ? WorkerStatus::Absconded : WorkerStatus::AwaitingDecision, $text, $order);

            $order->forceFill(['returned_at' => $date, 'return_reason_id' => $reason->getKey(), 'return_note' => $note]);
            $this->setStatus($order, RecruitmentStatus::Returned);

            $this->audit->log('recruitment.returned', $order, [...$this->params($order), 'reason' => $reason->translations('name')], new: [
                'returned_at' => $date,
                'absconded' => $absconded,
                'note' => $note,
            ]);

            return $order;
        });
    }

    /**
     * Cancel before the contract. A selected candidate goes back to
     * "ready for selection".
     */
    public function cancel(RecruitmentOrder $order, Lookup $reason, ?string $note = null): RecruitmentOrder
    {
        return DB::transaction(function () use ($order, $reason, $note): RecruitmentOrder {
            $order = $this->lock($order);

            if (! $order->status->isCancellable()) {
                throw RecruitmentException::because('not_cancellable');
            }

            if ($order->status === RecruitmentStatus::CandidateSelected) {
                $this->statuses->transition($order->worker, WorkerStatus::ReadyForSelection, __('recruitment.reasons.cancelled', ['number' => $order->number]), $order);
                $order->worker_id = null;
            }

            $order->forceFill(['cancelled_at' => now(), 'cancel_reason_id' => $reason->getKey(), 'cancel_note' => $note]);
            $this->setStatus($order, RecruitmentStatus::Cancelled);

            $this->audit->log('recruitment.cancelled', $order, [...$this->params($order), 'reason' => $reason->translations('name')], new: ['note' => $note]);

            return $order;
        });
    }

    /**
     * Re-read the order under lock and check its status.
     */
    private function lock(RecruitmentOrder $order, ?RecruitmentStatus $expected = null): RecruitmentOrder
    {
        $locked = RecruitmentOrder::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($order->getKey());

        if ($expected && $locked->status !== $expected) {
            throw RecruitmentException::because('wrong_status', ['status' => $locked->status->label()]);
        }

        return $locked;
    }

    private function setStatus(RecruitmentOrder $order, RecruitmentStatus $status): void
    {
        RecruitmentOrder::withStatusChange(fn () => $order->forceFill(['status' => $status])->save());
    }

    /**
     * Order number RQ-YYYY-0001, sequential per year.
     */
    private function nextNumber(): string
    {
        $prefix = 'RQ-'.now()->format('Y').'-';
        $last = RecruitmentOrder::query()->withoutGlobalScopes()->where('number', 'like', $prefix.'%')->lockForUpdate()->max('number');
        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function params(RecruitmentOrder $order): array
    {
        return ['number' => $order->number, 'client' => $order->client?->translations('name')];
    }
}

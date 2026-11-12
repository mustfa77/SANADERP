<?php

namespace App\Services\Cases;

use App\Enums\CaseDecision;
use App\Enums\CaseStatus;
use App\Enums\WorkerStatus;
use App\Models\Client;
use App\Models\Worker;
use App\Models\WorkerCase;
use App\Models\WorkerCaseNote;
use App\Models\WorkerStatusHistory;
use App\Services\AuditLogger;
use App\Services\Workers\WorkerStatusService;
use App\Support\SequenceNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Cases of workers waiting for a decision. The worker status follows
 * through WorkerStatusService (related = the case):
 *
 *   open        worker awaiting_decision (opened automatically)
 *   decide      transfer → for_transfer, deportation → for_deportation (case in progress),
 *               back to work → available (case closed)
 *   complete    transfer → transferred, deportation → deported (case closed)
 */
class WorkerCaseService
{
    public function __construct(private WorkerStatusService $statuses, private AuditLogger $audit) {}

    /**
     * Open a case for the worker, or return the case already open.
     */
    public function open(Worker $worker, ?string $reason = null, ?Model $related = null): WorkerCase
    {
        return DB::transaction(function () use ($worker, $reason, $related): WorkerCase {
            $existing = WorkerCase::query()->withoutGlobalScopes()->where('worker_id', $worker->getKey())
                ->where('status', '!=', CaseStatus::Closed)->lockForUpdate()->first();

            if ($existing) {
                return $existing;
            }

            $case = new WorkerCase;
            $case->forceFill([
                'number' => SequenceNumber::next(WorkerCase::class, 'CS'),
                'branch_id' => $worker->branch_id,
                'worker_id' => $worker->getKey(),
                'reason' => $reason ?? $this->latestReason($worker),
                'opened_at' => now(),
                'opened_by' => Auth::id(),
            ]);

            if ($related) {
                $case->related()->associate($related);
            }

            $case->save();

            $this->audit->log('case.opened', $case, ['number' => $case->number, 'worker' => $worker->translations('name')], new: ['reason' => $case->reason]);

            return $case;
        });
    }

    /**
     * Take the decision (cases.decide).
     *
     * @param  array{decision_note?: ?string, new_sponsor_client_id?: ?int, new_sponsor_name?: ?string, new_sponsor_id_number?: ?string, transfer_fee?: float|string|null, flight_date?: ?string, ticket_cost?: float|string|null}  $data
     */
    public function decide(WorkerCase $case, CaseDecision $decision, array $data = []): WorkerCase
    {
        return DB::transaction(function () use ($case, $decision, $data): WorkerCase {
            $case = $this->lock($case, CaseStatus::Open);
            $worker = $case->worker;

            if ($worker->status !== WorkerStatus::AwaitingDecision) {
                throw new CaseException(__('cases.errors.worker_not_awaiting', ['status' => $worker->status->label()]));
            }

            if ($decision === CaseDecision::Transfer && ! filled($data['new_sponsor_client_id'] ?? null) && ! filled($data['new_sponsor_name'] ?? null)) {
                throw new CaseException(__('cases.errors.sponsor_required'));
            }

            $this->statuses->transition($worker, $decision->workerStatus(), __('cases.reasons.decided', ['number' => $case->number, 'decision' => $decision->label()]), $case);

            $case->forceFill([
                'decision' => $decision,
                'decision_note' => $data['decision_note'] ?? null,
                'decided_at' => now(),
                'decided_by' => Auth::id(),
                ...$this->decisionDetails($decision, $data),
                'status' => $decision->finalStatus() ? CaseStatus::InProgress : CaseStatus::Closed,
                'completed_at' => $decision->finalStatus() ? null : today(),
                'completed_by' => $decision->finalStatus() ? null : Auth::id(),
            ])->save();

            $this->audit->log('case.decided', $case, ['number' => $case->number, 'worker' => $worker->translations('name'), 'decision' => $this->decisionParam($decision)], new: [
                'decision' => $decision->value,
                'note' => $data['decision_note'] ?? null,
            ]);

            return $case;
        });
    }

    /**
     * Transfer done / worker deported: final status, case closed.
     *
     * @param  array{completed_at: string, flight_date?: ?string, ticket_cost?: float|string|null, transfer_fee?: float|string|null, note?: ?string}  $data
     */
    public function complete(WorkerCase $case, array $data): WorkerCase
    {
        return DB::transaction(function () use ($case, $data): WorkerCase {
            $case = $this->lock($case, CaseStatus::InProgress);
            $final = $case->decision->finalStatus();
            $date = Carbon::parse($data['completed_at'])->startOfDay();

            if ($date->lt($case->decided_at->copy()->startOfDay())) {
                throw new CaseException(__('cases.errors.date_before_decision'));
            }

            $this->statuses->transition($case->worker, $final, __('cases.reasons.completed', ['number' => $case->number, 'decision' => $case->decision->label()]), $case);
            $case->worker->forceFill(['camp_id' => null])->save();

            $case->forceFill([
                'status' => CaseStatus::Closed,
                'completed_at' => $date,
                'completed_by' => Auth::id(),
                'flight_date' => $data['flight_date'] ?? $case->flight_date,
                'ticket_cost' => filled($data['ticket_cost'] ?? null) ? $data['ticket_cost'] : $case->ticket_cost,
                'transfer_fee' => filled($data['transfer_fee'] ?? null) ? $data['transfer_fee'] : $case->transfer_fee,
            ])->save();

            if (filled($data['note'] ?? null)) {
                $this->addNote($case, $data['note']);
            }

            $this->audit->log('case.completed', $case, ['number' => $case->number, 'worker' => $case->worker->translations('name'), 'decision' => $this->decisionParam($case->decision)], new: ['completed_at' => $date->toDateString()]);

            return $case;
        });
    }

    public function addNote(WorkerCase $case, string $note): WorkerCaseNote
    {
        $row = new WorkerCaseNote(['note' => $note]);
        $row->forceFill(['worker_case_id' => $case->getKey(), 'created_by' => Auth::id()])->save();

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function decisionDetails(CaseDecision $decision, array $data): array
    {
        return match ($decision) {
            CaseDecision::Transfer => [
                'new_sponsor_client_id' => filled($data['new_sponsor_client_id'] ?? null) ? (int) $data['new_sponsor_client_id'] : null,
                'new_sponsor_name' => $data['new_sponsor_name'] ?? (filled($data['new_sponsor_client_id'] ?? null) ? Client::query()->withoutGlobalScopes()->find($data['new_sponsor_client_id'])?->trans('name') : null),
                'new_sponsor_id_number' => $data['new_sponsor_id_number'] ?? null,
                'transfer_fee' => filled($data['transfer_fee'] ?? null) ? $data['transfer_fee'] : null,
            ],
            CaseDecision::Deportation => [
                'flight_date' => $data['flight_date'] ?? null,
                'ticket_cost' => filled($data['ticket_cost'] ?? null) ? $data['ticket_cost'] : null,
            ],
            CaseDecision::ReturnToWork => [],
        };
    }

    private function lock(WorkerCase $case, CaseStatus $expected): WorkerCase
    {
        $locked = WorkerCase::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($case->getKey());

        if ($locked->status !== $expected) {
            throw new CaseException(__('cases.errors.wrong_status', ['status' => $locked->status->label()]));
        }

        return $locked;
    }

    /**
     * Reason of the status change that put the worker in awaiting_decision.
     */
    private function latestReason(Worker $worker): ?string
    {
        return WorkerStatusHistory::query()->where('worker_id', $worker->getKey())
            ->where('to_status', WorkerStatus::AwaitingDecision->value)->latest('id')->value('reason');
    }

    /**
     * @return array{ar: string, en: string}
     */
    private function decisionParam(CaseDecision $decision): array
    {
        return ['ar' => __('cases.decisions.'.$decision->value, [], 'ar'), 'en' => __('cases.decisions.'.$decision->value, [], 'en')];
    }
}

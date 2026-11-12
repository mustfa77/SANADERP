<?php

namespace App\Services\Workers;

use App\Enums\WorkerStatus;
use App\Events\WorkerStatusChanged;
use App\Models\Worker;
use App\Models\WorkerStatusHistory;
use App\Services\AuditLogger;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The only way to change a worker's status. Rules come from
 * config/workflow.php. Every change is locked, recorded in
 * worker_status_history, audited and announced with WorkerStatusChanged.
 */
class WorkerStatusService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @throws InvalidStatusTransition
     */
    public function transition(Worker $worker, WorkerStatus $to, ?string $reason = null, ?Model $related = null): Worker
    {
        $from = DB::transaction(function () use ($worker, $to, $reason, $related): WorkerStatus {
            // Re-read the row under lock: the in-memory status may be stale.
            $locked = Worker::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($worker->getKey());
            $from = $locked->status;

            $this->guard($locked, $to);
            $this->apply($locked, $from, $to, $reason, $related);

            return $from;
        });

        $worker->refresh();
        WorkerStatusChanged::dispatch($worker, $from, $to, $related);

        return $worker;
    }

    /**
     * Record the starting status of a newly created worker (from = null).
     * Only statuses in workflow.initial are accepted.
     *
     * @throws InvalidStatusTransition
     */
    public function initialize(Worker $worker, WorkerStatus $status, ?string $reason = null): Worker
    {
        if (! in_array($status->value, config('workflow.initial'), true)) {
            throw new InvalidStatusTransition(null, $status, 'workers.status.not_initial');
        }

        DB::transaction(function () use ($worker, $status, $reason): void {
            $locked = Worker::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($worker->getKey());
            $this->apply($locked, null, $status, $reason, null);
        });

        $worker->refresh();
        WorkerStatusChanged::dispatch($worker, null, $status, null);

        return $worker;
    }

    public function canTransition(Worker $worker, WorkerStatus $to): bool
    {
        return in_array($to->value, config("workflow.transitions.{$worker->status->value}", []), true)
            && $this->conditionsMet($worker, $to);
    }

    /**
     * Statuses the worker can move to now (all triggers).
     *
     * @return list<WorkerStatus>
     */
    public function allowedTransitions(Worker $worker): array
    {
        return array_values(array_filter(
            array_map(fn (string $value): WorkerStatus => WorkerStatus::from($value), config("workflow.transitions.{$worker->status->value}", [])),
            fn (WorkerStatus $to): bool => $this->conditionsMet($worker, $to),
        ));
    }

    /**
     * Allowed transitions a user may pick with the "Change status" button.
     *
     * @return list<WorkerStatus>
     */
    public function manualTransitions(Worker $worker): array
    {
        $manual = config('workflow.manual');

        return array_values(array_filter(
            $this->allowedTransitions($worker),
            function (WorkerStatus $to) use ($manual, $worker): bool {
                if (! array_key_exists($to->value, $manual)) {
                    return false;
                }

                $from = $manual[$to->value]['from'] ?? null;

                return $from === null || in_array($worker->status->value, $from, true);
            },
        ));
    }

    /**
     * Allowed candidate review transitions (workflow.review), only while the
     * candidate is being screened (before selection for an order).
     *
     * @return list<WorkerStatus>
     */
    public function reviewTransitions(Worker $worker): array
    {
        $screening = [WorkerStatus::CandidateNew, WorkerStatus::UnderReview, WorkerStatus::Rejected];

        if (! in_array($worker->status, $screening, true)) {
            return [];
        }

        return array_values(array_filter(
            $this->allowedTransitions($worker),
            fn (WorkerStatus $to): bool => in_array($to->value, config('workflow.review'), true),
        ));
    }

    /**
     * @throws InvalidStatusTransition
     */
    private function guard(Worker $worker, WorkerStatus $to): void
    {
        if (! in_array($to->value, config("workflow.transitions.{$worker->status->value}", []), true)) {
            throw new InvalidStatusTransition($worker->status, $to);
        }

        if (! $this->conditionsMet($worker, $to)) {
            throw new InvalidStatusTransition($worker->status, $to, 'workers.status.condition_failed');
        }
    }

    /**
     * Extra conditions (e.g. arrived -> delivered only for client sponsorship).
     */
    private function conditionsMet(Worker $worker, WorkerStatus $to): bool
    {
        $conditions = config("workflow.conditions.{$worker->status->value}.{$to->value}", []);

        foreach ($conditions as $attribute => $expected) {
            $actual = $worker->getAttribute($attribute);

            if (($actual instanceof BackedEnum ? $actual->value : $actual) !== $expected) {
                return false;
            }
        }

        return true;
    }

    private function apply(Worker $worker, ?WorkerStatus $from, WorkerStatus $to, ?string $reason, ?Model $related): void
    {
        $now = now();

        Worker::withStatusChange(fn () => $worker->forceFill(['status' => $to, 'status_changed_at' => $now])->save());

        WorkerStatusHistory::query()->create([
            'worker_id' => $worker->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'changed_by' => Auth::id(),
            'changed_at' => $now,
        ]);

        $this->audit->log(
            'worker.status_changed',
            $worker,
            [
                'name' => $worker->translations('name'),
                'from' => $from ? ['ar' => __("workers.statuses.{$from->value}", [], 'ar'), 'en' => __("workers.statuses.{$from->value}", [], 'en')] : '—',
                'to' => ['ar' => __("workers.statuses.{$to->value}", [], 'ar'), 'en' => __("workers.statuses.{$to->value}", [], 'en')],
            ],
            ['status' => $from?->value],
            ['status' => $to->value, 'reason' => $reason],
        );
    }
}

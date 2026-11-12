<?php

namespace App\Services\Camps;

use App\Enums\AttendanceStatus;
use App\Enums\WorkerStatus;
use App\Models\Camp;
use App\Models\CampAttendance;
use App\Models\CampMovement;
use App\Models\Worker;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Housing operations: manual check-in / transfer / check-out (the movement
 * itself is logged by the Worker model), occupancy and the daily roll-call.
 */
class CampService
{
    /**
     * Statuses of workers that live in a housing (in the country and not
     * with a client).
     *
     * @var list<WorkerStatus>
     */
    public const HOUSED_STATUSES = [
        WorkerStatus::Arrived, WorkerStatus::Available, WorkerStatus::Sick, WorkerStatus::Suspended,
        WorkerStatus::AwaitingDecision, WorkerStatus::ForDeportation, WorkerStatus::ForTransfer,
    ];

    public function __construct(private AuditLogger $audit) {}

    public function occupancy(Camp $camp): int
    {
        return Worker::query()->withoutGlobalScopes()->where('camp_id', $camp->getKey())->count();
    }

    /**
     * Workers of the camp's branch that may be checked in (housed statuses,
     * not already in this camp).
     *
     * @return Builder<Worker>
     */
    public function movableWorkers(Camp $camp): Builder
    {
        return Worker::query()->withoutGlobalScopes()
            ->where('branch_id', $camp->branch_id)
            ->whereIn('status', self::HOUSED_STATUSES)
            ->where(fn (Builder $query) => $query->whereNull('camp_id')->orWhere('camp_id', '!=', $camp->getKey()));
    }

    /**
     * Move the worker into $camp (check-in or transfer), or out of any
     * housing when $camp is null (check-out). A note is required.
     */
    public function move(Worker $worker, ?Camp $camp, string $note): Worker
    {
        return DB::transaction(function () use ($worker, $camp, $note): Worker {
            $worker = Worker::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($worker->getKey());

            if ($camp) {
                if ($camp->branch_id !== $worker->branch_id || ! $camp->is_active) {
                    throw new CampException(__('camps.errors.camp_not_allowed'));
                }

                if (! in_array($worker->status, self::HOUSED_STATUSES, true)) {
                    throw new CampException(__('camps.errors.status_not_housed', ['status' => $worker->status->label()]));
                }

                if ($camp->capacity && $this->occupancy($camp) >= $camp->capacity) {
                    throw new CampException(__('camps.errors.camp_full', ['camp' => $camp->trans('name')]));
                }
            }

            if ((int) $worker->camp_id === (int) $camp?->getKey()) {
                throw new CampException(__('camps.errors.same_camp'));
            }

            $from = $worker->camp;
            CampMovement::withNote($note, fn () => $worker->forceFill(['camp_id' => $camp?->getKey()])->save());

            $this->audit->log('camp.moved', $worker, [
                'name' => $worker->translations('name'),
                'from' => $from?->translations('name') ?? ['ar' => '—', 'en' => '—'],
                'to' => $camp?->translations('name') ?? ['ar' => '—', 'en' => '—'],
            ], ['camp_id' => $from?->getKey()], ['camp_id' => $camp?->getKey(), 'note' => $note]);

            return $worker;
        });
    }

    /**
     * Save the roll-call of the camp for $date.
     *
     * @param  array<int, array{status: string, note?: ?string}>  $rows  worker_id => row
     * @return int saved rows
     */
    public function recordAttendance(Camp $camp, Carbon $date, array $rows): int
    {
        if ($date->isFuture() && ! $date->isToday()) {
            throw new CampException(__('camps.errors.future_date'));
        }

        $workerIds = Worker::query()->withoutGlobalScopes()->where('camp_id', $camp->getKey())->pluck('id')
            ->merge(CampAttendance::query()->withoutGlobalScopes()->where('camp_id', $camp->getKey())->whereDate('date', $date)->pluck('worker_id'))
            ->unique();

        return DB::transaction(function () use ($camp, $date, $rows, $workerIds): int {
            $saved = 0;

            foreach ($rows as $workerId => $row) {
                $status = AttendanceStatus::tryFrom((string) ($row['status'] ?? ''));

                // Only workers living in (or already counted in) this camp.
                if (! $status || ! $workerIds->contains((int) $workerId)) {
                    continue;
                }

                $attendance = CampAttendance::query()->withoutGlobalScopes()->where('worker_id', (int) $workerId)->whereDate('date', $date)->first()
                    ?? new CampAttendance(['worker_id' => (int) $workerId, 'date' => $date]);
                $attendance->fill(['branch_id' => $camp->branch_id, 'camp_id' => $camp->getKey(), 'status' => $status, 'note' => $row['note'] ?? null, 'recorded_by' => Auth::id()])->save();
                $saved++;
            }

            $this->audit->log('camp.attendance', $camp, ['camp' => $camp->translations('name'), 'date' => $date->toDateString()], new: ['rows' => $saved]);

            return $saved;
        });
    }
}

<?php

namespace App\Services\Alerts;

use App\Enums\AlertType;
use App\Enums\AttendanceStatus;
use App\Enums\CaseStatus;
use App\Enums\RecruitmentStatus;
use App\Enums\RentalContractStatus;
use App\Enums\TripStatus;
use App\Enums\WorkerStatus;
use App\Models\Alert;
use App\Models\CampAttendance;
use App\Models\DriverProfile;
use App\Models\RecruitmentOrder;
use App\Models\RecruitmentOrderStage;
use App\Models\RentalContract;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the in-system alerts from the data and the alert periods in
 * Settings > Alerts. refresh() adds new alerts, updates the existing ones
 * and closes the alerts whose condition is gone. One alert per subject and
 * due date ("key"), so a handled alert does not come back for the same date.
 */
class AlertService
{
    /** Days before a driver's licence expires. */
    private const LICENSE_DAYS = 30;

    /** Days a case may wait for a decision before an alert. */
    private const CASE_DAYS = 3;

    /**
     * Statuses of workers whose papers no longer matter.
     *
     * @var list<WorkerStatus>
     */
    private const GONE = [WorkerStatus::Deported, WorkerStatus::Transferred, WorkerStatus::Absconded, WorkerStatus::Rejected];

    /**
     * @return array{created: int, updated: int, resolved: int}
     */
    public function refresh(): array
    {
        $candidates = $this->candidates()->keyBy('key');

        return DB::transaction(function () use ($candidates): array {
            $existing = Alert::query()->withoutGlobalScopes()->whereIn('key', $candidates->keys())->get()->keyBy('key');
            $created = $updated = 0;

            foreach ($candidates as $key => $row) {
                $alert = $existing->get($key);

                if (! $alert) {
                    $alert = new Alert;
                    $alert->forceFill(['key' => $key, 'type' => $row['type'], 'subject_type' => $row['subject']->getMorphClass(), 'subject_id' => $row['subject']->getKey()]);
                    $created++;
                } elseif ($alert->resolved_at === null) {
                    $updated++;
                }

                // A handled alert stays handled; its texts are still refreshed.
                $alert->forceFill([
                    'branch_id' => $row['branch_id'],
                    'level' => $row['level'],
                    'due_at' => $row['due_at'],
                    'params' => $row['params'],
                ])->save();
            }

            // Condition gone (renewed iqama, contract closed...): close by itself.
            $resolved = Alert::query()->withoutGlobalScopes()->whereNull('resolved_at')->whereNotIn('key', $candidates->keys())
                ->update(['resolved_at' => now(), 'updated_at' => now()]);

            return ['created' => $created, 'updated' => $updated, 'resolved' => $resolved];
        });
    }

    public function resolve(Alert $alert, User $user): Alert
    {
        $alert->forceFill(['resolved_at' => now(), 'resolved_by' => $user->getKey()])->save();

        return $alert;
    }

    /**
     * Every alert that should be open now.
     *
     * @return Collection<int, array{key: string, type: AlertType, subject: Model, branch_id: int, level: string, due_at: ?Carbon, params: array<string, mixed>}>
     */
    public function candidates(): Collection
    {
        $today = today();

        return collect()
            ->concat($this->documentExpiry(AlertType::IqamaExpiry, 'iqama_expiry', Setting::integer('days_before_iqama_expiry')))
            ->concat($this->documentExpiry(AlertType::PassportExpiry, 'passport_expiry', Setting::integer('days_before_passport_expiry')))
            ->concat($this->documentExpiry(AlertType::MedicalExpiry, 'medical_expiry', Setting::integer('days_before_medical_expiry')))
            ->concat(RentalContract::query()->withoutGlobalScopes()->with(['worker', 'client'])
                ->where('status', RentalContractStatus::Active)
                ->whereDate('ends_at', '<=', $today->copy()->addDays(Setting::integer('days_before_contract_end')))
                ->get()
                ->map(fn (RentalContract $contract) => $this->row(AlertType::ContractEnd, $contract, $contract->ends_at, [
                    'number' => $contract->number, 'worker' => $contract->worker?->translations('name'), 'client' => $contract->client?->translations('name'), 'date' => $contract->ends_at->toDateString(),
                ])))
            ->concat(RecruitmentOrder::query()->withoutGlobalScopes()->with(['worker', 'client'])
                ->where('status', RecruitmentStatus::Delivered)
                ->whereDate('probation_ends_at', '<=', $today->copy()->addDays(Setting::integer('days_before_probation_end')))
                ->get()
                ->map(fn (RecruitmentOrder $order) => $this->row(AlertType::ProbationEnd, $order, $order->probation_ends_at, [
                    'number' => $order->number, 'worker' => $order->worker?->translations('name'), 'client' => $order->client?->translations('name'), 'date' => $order->probation_ends_at->toDateString(),
                ])))
            ->concat(RecruitmentOrderStage::query()->with('order')
                ->whereNull('completed_at')->whereDate('due_at', '<', $today)
                ->whereHas('order', fn ($query) => $query->withoutGlobalScopes()->where('status', RecruitmentStatus::InTravel))
                ->get()
                ->map(fn (RecruitmentOrderStage $stage) => $this->row(AlertType::OverdueStage, $stage->order, $stage->due_at, [
                    'number' => $stage->order->number, 'stage' => $stage->translations('name'), 'days' => (int) $stage->due_at->diffInDays($today),
                ], 'danger')))
            ->concat($this->expiredHolds())
            ->concat(CampAttendance::query()->withoutGlobalScopes()->with(['worker', 'camp'])
                ->whereDate('date', $today)->where('status', AttendanceStatus::Absent)
                ->get()
                ->map(fn (CampAttendance $row) => $this->row(AlertType::WorkerAbsent, $row->worker, $row->date, [
                    'worker' => $row->worker?->translations('name'), 'camp' => $row->camp?->translations('name'),
                ], 'danger')))
            ->concat(Trip::query()->withoutGlobalScopes()
                ->where('status', TripStatus::Scheduled)->whereNull('driver_id')
                ->whereBetween('scheduled_at', [$today->copy()->startOfDay(), now()->addDay()])
                ->get()
                ->map(fn (Trip $trip) => $this->row(AlertType::TripUnassigned, $trip, $trip->scheduled_at->copy()->startOfDay(), [
                    'number' => $trip->number, 'time' => $trip->scheduled_at->format('Y-m-d H:i'),
                ], $trip->scheduled_at->lt(now()->addHours(3)) ? 'danger' : 'warning')))
            ->concat(DriverProfile::query()->withoutGlobalScopes()->with('user')
                ->where('is_active', true)->whereNotNull('license_expiry')
                ->whereDate('license_expiry', '<=', $today->copy()->addDays(self::LICENSE_DAYS))
                ->get()
                ->map(fn (DriverProfile $profile) => $this->row(AlertType::LicenseExpiry, $profile, $profile->license_expiry, [
                    'driver' => (string) $profile->user?->displayName(), 'date' => $profile->license_expiry->toDateString(),
                ])))
            ->concat(WorkerCase::query()->withoutGlobalScopes()->with('worker')
                ->where('status', CaseStatus::Open)->where('opened_at', '<=', now()->subDays(self::CASE_DAYS))
                ->get()
                ->map(fn (WorkerCase $case) => $this->row(AlertType::CaseWaiting, $case, $case->opened_at->copy()->startOfDay(), [
                    'number' => $case->number, 'worker' => $case->worker?->translations('name'), 'days' => $case->ageInDays(),
                ])))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function documentExpiry(AlertType $type, string $column, int $days): Collection
    {
        return Worker::query()->withoutGlobalScopes()
            ->whereNotNull($column)
            ->whereDate($column, '<=', today()->addDays($days))
            ->whereNotIn('status', self::GONE)
            ->get()
            ->map(fn (Worker $worker) => $this->row($type, $worker, $worker->{$column}, [
                'worker' => $worker->translations('name'), 'number' => $worker->number(), 'date' => $worker->{$column}->toDateString(),
            ]));
    }

    /**
     * Candidates held for a client longer than the hold duration (Settings).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function expiredHolds(): Collection
    {
        $limit = now()->subHours(Setting::integer('hold_duration_hours'));

        return RecruitmentOrder::query()->withoutGlobalScopes()->with(['worker', 'client'])
            ->where('status', RecruitmentStatus::CandidateSelected)
            ->whereHas('worker', fn ($query) => $query->withoutGlobalScopes()->where('status', WorkerStatus::OnHold)->where('status_changed_at', '<=', $limit))
            ->get()
            ->map(fn (RecruitmentOrder $order) => $this->row(AlertType::HoldExpired, $order, $order->worker->status_changed_at->copy()->startOfDay(), [
                'number' => $order->number, 'worker' => $order->worker->translations('name'), 'client' => $order->client?->translations('name'),
            ]));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{key: string, type: AlertType, subject: Model, branch_id: int, level: string, due_at: ?Carbon, params: array<string, mixed>}
     */
    private function row(AlertType $type, Model $subject, ?Carbon $due, array $params, ?string $level = null): array
    {
        return [
            'key' => $type->value.':'.class_basename($subject).':'.$subject->getKey().':'.$due?->toDateString(),
            'type' => $type,
            'subject' => $subject,
            'branch_id' => (int) $subject->branch_id,
            'level' => $level ?? ($due && $due->lt(today()) ? 'danger' : 'warning'),
            'due_at' => $due?->copy()->startOfDay(),
            'params' => $params,
        ];
    }
}

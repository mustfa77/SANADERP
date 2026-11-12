<?php

namespace App\Enums;

/**
 * Worker lifecycle status. Allowed transitions live in config/workflow.php;
 * only App\Services\Workers\WorkerStatusService changes a worker's status.
 * Hourly rental is not a status (an hourly-rented worker stays "available").
 */
enum WorkerStatus: string
{
    // Recruitment
    case CandidateNew = 'candidate_new';
    case UnderReview = 'under_review';
    case Rejected = 'rejected';
    case ReadyForSelection = 'ready_for_selection';
    case OnHold = 'on_hold';
    case Contracted = 'contracted';
    case InTravelProcess = 'in_travel_process';
    case Arrived = 'arrived';

    // Client sponsorship
    case Delivered = 'delivered';
    case InProbation = 'in_probation';
    case ProbationCompleted = 'probation_completed';

    // Company sponsorship
    case Available = 'available';
    case RentedMonthly = 'rented_monthly';

    // Special
    case Sick = 'sick';
    case Suspended = 'suspended';
    case ExitReentry = 'exit_reentry';
    case AwaitingDecision = 'awaiting_decision';
    case ForDeportation = 'for_deportation';
    case ForTransfer = 'for_transfer';

    // Final
    case Deported = 'deported';
    case Transferred = 'transferred';
    case Absconded = 'absconded';

    public function label(): string
    {
        return __("workers.statuses.{$this->value}");
    }

    public function group(): string
    {
        return match ($this) {
            self::CandidateNew, self::UnderReview, self::Rejected, self::ReadyForSelection,
            self::OnHold, self::Contracted, self::InTravelProcess, self::Arrived => 'recruitment',
            self::Delivered, self::InProbation, self::ProbationCompleted => 'client_sponsorship',
            self::Available, self::RentedMonthly => 'company_sponsorship',
            self::Sick, self::Suspended, self::ExitReentry, self::AwaitingDecision,
            self::ForDeportation, self::ForTransfer => 'special',
            self::Deported, self::Transferred, self::Absconded => 'final',
        };
    }

    /**
     * Bootstrap colour of the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::CandidateNew, self::UnderReview => 'info',
            self::Rejected, self::Absconded, self::Deported => 'danger',
            self::ReadyForSelection, self::Available, self::ProbationCompleted => 'success',
            self::OnHold, self::AwaitingDecision, self::Sick, self::ExitReentry => 'warning',
            self::Contracted, self::InTravelProcess, self::Arrived, self::RentedMonthly, self::Delivered, self::InProbation => 'primary',
            self::Suspended, self::ForDeportation, self::ForTransfer, self::Transferred => 'secondary',
        };
    }

    public function isFinal(): bool
    {
        return $this->group() === 'final';
    }

    /**
     * Cases grouped for filters: group => list of cases.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $case) {
            $groups[$case->group()][] = $case;
        }

        return $groups;
    }
}

<?php

namespace App\Enums;

/**
 * Recruitment order status. Changed only by RecruitmentService.
 * "delivered" = handed over to the client and in probation.
 */
enum RecruitmentStatus: string
{
    case New = 'new';
    case CandidateSelected = 'candidate_selected';
    case Contracted = 'contracted';
    case InTravel = 'in_travel';
    case Arrived = 'arrived';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("recruitment.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'info',
            self::CandidateSelected => 'warning',
            self::Contracted, self::InTravel => 'primary',
            self::Arrived, self::Delivered => 'success',
            self::Completed => 'secondary',
            self::Returned, self::Cancelled => 'danger',
        };
    }

    /**
     * Still in progress (the order keeps its worker; probation included).
     */
    public function isOpen(): bool
    {
        return ! in_array($this, self::closed(), true);
    }

    /**
     * @return list<self>
     */
    public static function closed(): array
    {
        return [self::Completed, self::Returned, self::Cancelled];
    }

    /**
     * Cancellation is possible until the contract is signed.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::New, self::CandidateSelected], true);
    }

    /**
     * The worker has arrived in the Kingdom (counts as a completed visa).
     */
    public function hasArrived(): bool
    {
        return in_array($this, [self::Arrived, self::Delivered, self::Completed, self::Returned], true);
    }
}

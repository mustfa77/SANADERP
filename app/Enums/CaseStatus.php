<?php

namespace App\Enums;

/**
 * open: waiting for a decision; in_progress: transfer / deportation decided,
 * not done yet; closed: done (or back to work).
 */
enum CaseStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return __("cases.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::InProgress => 'primary',
            self::Closed => 'secondary',
        };
    }
}

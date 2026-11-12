<?php

namespace App\Enums;

/**
 * Monthly rental contract status. Changed only by RentalContractService.
 */
enum RentalContractStatus: string
{
    /** The worker is with the client. */
    case Active = 'active';

    /** Closed by a renewal (the next month's contract continues it). */
    case Renewed = 'renewed';

    /** The worker came back at (or after) the end date. */
    case Ended = 'ended';

    /** The worker came back before the end date. */
    case Terminated = 'terminated';

    /** Cancelled: nothing is charged, the payments are refunded. */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("rental.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Renewed => 'info',
            self::Ended => 'secondary',
            self::Terminated => 'danger',
            self::Cancelled => 'dark',
        };
    }
}

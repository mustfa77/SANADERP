<?php

namespace App\Enums;

/**
 * Hourly booking status. Changed only by HourlyBookingService.
 */
enum HourlyBookingStatus: string
{
    case Booked = 'booked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("hourly.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Booked => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}

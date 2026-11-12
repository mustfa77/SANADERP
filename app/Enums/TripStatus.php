<?php

namespace App\Enums;

enum TripStatus: string
{
    case Scheduled = 'scheduled';
    case Started = 'started';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("transport.statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Started => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'secondary',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Scheduled, self::Started], true);
    }
}

<?php

namespace App\Enums;

/**
 * Daily roll-call result of a worker in a housing.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Sick = 'sick';
    case Outside = 'outside';

    public function label(): string
    {
        return __("camps.attendance_statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent => 'danger',
            self::Sick => 'warning',
            self::Outside => 'info',
        };
    }
}

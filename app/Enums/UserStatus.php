<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Locked = 'locked';

    public function label(): string
    {
        return __("users.statuses.{$this->value}");
    }

    /**
     * Bootstrap colour used by status badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'secondary',
            self::Locked => 'danger',
        };
    }
}

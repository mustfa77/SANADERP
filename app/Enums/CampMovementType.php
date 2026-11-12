<?php

namespace App\Enums;

enum CampMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Transfer = 'transfer';

    public function label(): string
    {
        return __("camps.movement_types.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'warning',
            self::Transfer => 'info',
        };
    }
}

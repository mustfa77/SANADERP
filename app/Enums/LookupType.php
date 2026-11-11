<?php

namespace App\Enums;

enum LookupType: string
{
    case ReturnReason = 'return_reason';
    case CancelReason = 'cancel_reason';
    case PaymentMethod = 'payment_method';

    public function label(): string
    {
        return __("reference.lookups.types.{$this->value}");
    }
}

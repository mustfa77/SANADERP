<?php

namespace App\Enums;

enum RentalType: string
{
    case Monthly = 'monthly';
    case Hourly = 'hourly';

    public function label(): string
    {
        return __("reference.prices.rental_types.{$this->value}");
    }
}

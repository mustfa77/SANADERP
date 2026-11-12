<?php

namespace App\Enums;

enum WorkerRentalType: string
{
    case None = 'none';
    case Monthly = 'monthly';
    case Hourly = 'hourly';

    public function label(): string
    {
        return __("workers.rental_types.{$this->value}");
    }
}

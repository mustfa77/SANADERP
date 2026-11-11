<?php

namespace App\Enums;

enum CampType: string
{
    case Rental = 'rental';
    case Individual = 'individual';
    case Mixed = 'mixed';

    public function label(): string
    {
        return __("reference.camps.types.{$this->value}");
    }
}

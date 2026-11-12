<?php

namespace App\Enums;

enum SponsorType: string
{
    case Client = 'client';
    case Company = 'company';

    public function label(): string
    {
        return __("workers.sponsor_types.{$this->value}");
    }
}

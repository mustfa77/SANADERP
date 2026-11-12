<?php

namespace App\Enums;

enum PassportType: string
{
    case Ordinary = 'ordinary';
    case Official = 'official';
    case Diplomatic = 'diplomatic';

    public function label(): string
    {
        return __("workers.passport_types.{$this->value}");
    }
}

<?php

namespace App\Enums;

enum Religion: string
{
    case Muslim = 'muslim';
    case Christian = 'christian';
    case Hindu = 'hindu';
    case Buddhist = 'buddhist';
    case Other = 'other';

    public function label(): string
    {
        return __("workers.religions.{$this->value}");
    }
}

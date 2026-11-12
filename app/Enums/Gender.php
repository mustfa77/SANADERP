<?php

namespace App\Enums;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';

    public function label(): string
    {
        return __("workers.genders.{$this->value}");
    }
}

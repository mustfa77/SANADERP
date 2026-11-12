<?php

namespace App\Enums;

enum CvCategory: string
{
    case FirstTimer = 'first_timer';
    case Experienced = 'experienced';

    public function label(): string
    {
        return __("cv.categories.{$this->value}");
    }
}

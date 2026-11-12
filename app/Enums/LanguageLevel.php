<?php

namespace App\Enums;

enum LanguageLevel: string
{
    case None = 'none';
    case Weak = 'weak';
    case Good = 'good';
    case Fluent = 'fluent';

    public function label(): string
    {
        return __("cv.language_levels.{$this->value}");
    }
}

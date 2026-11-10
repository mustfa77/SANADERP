<?php

namespace App\Enums;

enum UserType: string
{
    case Internal = 'internal';
    case ExternalAgency = 'external_agency';
    case Driver = 'driver';

    public function label(): string
    {
        return __("users.types.{$this->value}");
    }

    /**
     * Named route the user lands on after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Internal => 'admin.dashboard',
            self::ExternalAgency => 'agency.candidates.index',
            self::Driver => 'driver.trips.index',
        };
    }
}

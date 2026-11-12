<?php

namespace App\Enums;

/**
 * Purpose of a trip. The automatic ones come from hourly bookings and
 * recruitment home deliveries; the coordinator adds manual ones.
 */
enum TripType: string
{
    case HourlyDropoff = 'hourly_dropoff';
    case HourlyPickup = 'hourly_pickup';
    case Delivery = 'delivery';
    case Manual = 'manual';

    public function label(): string
    {
        return __("transport.types.{$this->value}");
    }

    public function icon(): string
    {
        return match ($this) {
            self::HourlyDropoff => 'ri-logout-circle-r-line',
            self::HourlyPickup => 'ri-login-circle-line',
            self::Delivery => 'ri-hand-heart-line',
            self::Manual => 'ri-route-line',
        };
    }
}

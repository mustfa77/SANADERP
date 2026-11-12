<?php

namespace App\Policies;

use App\Models\HourlyBooking;
use App\Models\User;

/**
 * Branch limits come from BranchScope (another branch's booking is a 404).
 */
class HourlyBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hourly_bookings.view');
    }

    public function view(User $user, HourlyBooking $booking): bool
    {
        return $user->can('hourly_bookings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('hourly_bookings.create');
    }

    public function update(User $user, HourlyBooking $booking): bool
    {
        return $user->can('hourly_bookings.edit');
    }

    public function print(User $user, HourlyBooking $booking): bool
    {
        return $user->can('hourly_bookings.print');
    }
}

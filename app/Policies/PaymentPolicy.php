<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Branch limits come from BranchScope; seeing a payment also needs the
 * permission of its contract / booking (checked by PaymentController).
 */
class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify') && ! $payment->isVerified();
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('payments.delete') && ! $payment->isVerified();
    }
}

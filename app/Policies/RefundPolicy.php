<?php

namespace App\Policies;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Models\User;

/**
 * Branch limits come from BranchScope (another branch's refund is a 404).
 */
class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('refunds.view');
    }

    public function decide(User $user, Refund $refund): bool
    {
        return $user->can('refunds.approve') && $refund->status === RefundStatus::Pending;
    }
}

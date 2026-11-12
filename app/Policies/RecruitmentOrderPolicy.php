<?php

namespace App\Policies;

use App\Models\RecruitmentOrder;
use App\Models\User;

/**
 * Branch limits come from BranchScope (another branch's order is a 404).
 */
class RecruitmentOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('recruitment.view');
    }

    public function view(User $user, RecruitmentOrder $order): bool
    {
        return $user->can('recruitment.view');
    }

    public function create(User $user): bool
    {
        return $user->can('recruitment.create');
    }

    public function update(User $user, RecruitmentOrder $order): bool
    {
        return $user->can('recruitment.edit');
    }

    public function export(User $user): bool
    {
        return $user->can('recruitment.export');
    }
}

<?php

namespace App\Policies;

use App\Enums\CaseStatus;
use App\Models\User;
use App\Models\WorkerCase;

/**
 * Branch limits come from BranchScope (another branch's case is a 404).
 */
class WorkerCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.view');
    }

    public function view(User $user, WorkerCase $case): bool
    {
        return $user->can('cases.view');
    }

    /**
     * Follow-up notes and completing a decided transfer / deportation.
     */
    public function update(User $user, WorkerCase $case): bool
    {
        return $user->can('cases.edit') && $case->status !== CaseStatus::Closed;
    }

    public function decide(User $user, WorkerCase $case): bool
    {
        return $user->can('cases.decide') && $case->status === CaseStatus::Open;
    }
}

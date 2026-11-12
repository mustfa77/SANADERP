<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Worker;

/**
 * Branch limits come from BranchScope (another branch's worker is a 404
 * before the policy runs); the policy checks the permission only.
 */
class WorkerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('workers.view');
    }

    public function view(User $user, Worker $worker): bool
    {
        return $user->can('workers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('workers.create');
    }

    public function update(User $user, Worker $worker): bool
    {
        return $user->can('workers.edit') && ! $worker->status->isFinal();
    }

    public function changeStatus(User $user, Worker $worker): bool
    {
        return $user->can('workers.change_status');
    }

    public function export(User $user): bool
    {
        return $user->can('workers.export');
    }
}

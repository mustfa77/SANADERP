<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Users without "branches.all" only reach their own branches; another
 * branch returns 404 (it does not exist for them).
 */
class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('branches.view');
    }

    public function view(User $user, Branch $branch): Response
    {
        return $this->allowed($user, $branch, 'branches.view');
    }

    public function create(User $user): bool
    {
        return $user->can('branches.create');
    }

    public function update(User $user, Branch $branch): Response
    {
        return $this->allowed($user, $branch, 'branches.edit');
    }

    public function delete(User $user, Branch $branch): Response
    {
        return $this->allowed($user, $branch, 'branches.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('branches.export');
    }

    private function allowed(User $user, Branch $branch, string $permission): Response
    {
        if (! in_array($branch->getKey(), $user->allowedBranchIds(), true)) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission) ? Response::allow() : Response::deny();
    }
}

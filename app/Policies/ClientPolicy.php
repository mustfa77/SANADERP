<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Branch limits come from BranchScope (another branch's client is a 404
 * before the policy runs); the policy checks the permission only.
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clients.view');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->can('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->can('clients.create');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can('clients.edit');
    }

    public function export(User $user): bool
    {
        return $user->can('clients.export');
    }
}

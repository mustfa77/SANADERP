<?php

namespace App\Policies;

use App\Models\RentalContract;
use App\Models\User;

/**
 * Branch limits come from BranchScope (another branch's contract is a 404).
 */
class RentalContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rental_contracts.view');
    }

    public function view(User $user, RentalContract $contract): bool
    {
        return $user->can('rental_contracts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rental_contracts.create');
    }

    public function update(User $user, RentalContract $contract): bool
    {
        return $user->can('rental_contracts.edit');
    }

    public function print(User $user, RentalContract $contract): bool
    {
        return $user->can('rental_contracts.print');
    }
}

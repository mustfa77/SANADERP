<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limits branch-owned records to the logged-in user's branches. When the
 * user picked a branch in the top-bar switcher, only that branch is shown.
 * Users with "branches.all" (or Super Admin) are not restricted unless they
 * selected a branch. Guests (console, queues, seeders) are not filtered.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $column = $model->qualifyColumn('branch_id');
        $selected = $user->selectedBranchId();

        if ($selected !== null) {
            $builder->where($column, $selected);

            return;
        }

        if ($user->hasAllBranches()) {
            return;
        }

        $builder->whereIn($column, $user->allowedBranchIds());
    }
}

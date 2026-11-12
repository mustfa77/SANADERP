<?php

namespace App\Policies;

use App\Models\User;
use App\Services\SuperAdminGuard;

/**
 * Abilities listed in AppServiceProvider::GUARDED_ABILITIES are evaluated
 * even for Super Admins (no Gate::before bypass), so self-protection and
 * last-Super-Admin rules always apply.
 */
class UserPolicy
{
    public function __construct(private SuperAdminGuard $guard) {}

    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.edit') && ($user->isSuperAdmin() || ! $model->isSuperAdmin());
    }

    /**
     * Change the roles, branches or direct permissions of a user. Nobody can
     * change their own access.
     */
    public function assignRoles(User $user, ?User $model = null): bool
    {
        if ($model !== null && $user->is($model)) {
            return false;
        }

        return $this->allowed($user, 'users.edit')
            && ($model === null || $user->isSuperAdmin() || ! $model->isSuperAdmin());
    }

    public function activate(User $user, User $model): bool
    {
        return $this->update($user, $model) && ! $user->is($model);
    }

    public function deactivate(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.edit')
            && ! $user->is($model)
            && ($user->isSuperAdmin() || ! $model->isSuperAdmin())
            && ! $this->guard->isLastActiveSuperAdmin($model);
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->allowed($user, 'users.delete')
            && ! $user->is($model)
            && ($user->isSuperAdmin() || ! $model->isSuperAdmin())
            && ! $this->guard->isLastActiveSuperAdmin($model);
    }

    /**
     * Permission check that also works for guarded abilities, where the
     * Super Admin bypass does not run.
     */
    private function allowed(User $user, string $permission): bool
    {
        return $user->isSuperAdmin() || $user->checkPermissionTo($permission);
    }
}

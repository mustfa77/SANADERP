<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/**
 * Abilities listed in AppServiceProvider::GUARDED_ABILITIES are evaluated
 * even for Super Admins (no Gate::before bypass).
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function clone(User $user, Role $role): bool
    {
        return $user->can('roles.create') && ! $role->isSuperAdmin();
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.edit') && ($user->isSuperAdmin() || ! $role->isSuperAdmin());
    }

    /**
     * Super Admin permissions can never be reduced, so its matrix is read-only.
     */
    public function updatePermissions(User $user, Role $role): bool
    {
        return ! $role->isSuperAdmin()
            && ($user->isSuperAdmin() || $user->checkPermissionTo('roles.edit'));
    }

    /**
     * System roles cannot be deleted. Roles still assigned to users are
     * rejected by the controller, which lists those users.
     */
    public function delete(User $user, Role $role): bool
    {
        return ! $role->is_system
            && ($user->isSuperAdmin() || $user->checkPermissionTo('roles.delete'));
    }
}

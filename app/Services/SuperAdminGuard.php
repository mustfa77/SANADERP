<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;

/**
 * Protects the last active Super Admin from being deactivated, deleted or
 * losing the role.
 */
class SuperAdminGuard
{
    public function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin() || ! $user->isActive()) {
            return false;
        }

        return User::role(config('permissions.super_admin_role'))
            ->where('status', UserStatus::Active)
            ->whereKeyNot($user->getKey())
            ->doesntExist();
    }
}

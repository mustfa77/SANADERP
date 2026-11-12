<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Assigns roles and branches to a user, logs before/after values and clears
 * the permission cache.
 */
class UserAccessManager
{
    public const ALL_BRANCHES_PERMISSION = 'branches.all';

    public function __construct(
        private AuditLogger $audit,
        private SuperAdminGuard $guard,
        private PermissionRegistrar $registrar,
    ) {}

    /**
     * @param  list<string>|null  $roles  Role names; null leaves roles unchanged.
     * @param  list<int>|null  $branchIds  Branch ids; null leaves branches unchanged.
     * @param  bool|null  $allBranches  Grant "branches.all"; null leaves it unchanged.
     *
     * @throws ValidationException
     */
    public function sync(User $user, ?array $roles, ?array $branchIds, ?bool $allBranches): void
    {
        if ($roles !== null) {
            $this->syncRoles($user, $roles);
        }

        if ($branchIds !== null || $allBranches !== null) {
            $this->syncBranches($user, $branchIds, $allBranches);
        }

        $this->registrar->forgetCachedPermissions();
        $user->flushAccessCache();
    }

    /**
     * @param  list<string>  $roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        $old = $user->getRoleNames()->sort()->values()->all();
        $new = collect($roles)->unique()->sort()->values()->all();

        if ($old === $new) {
            return;
        }

        $superAdminRole = config('permissions.super_admin_role');

        if (in_array($superAdminRole, $old, true) && ! in_array($superAdminRole, $new, true)
            && $this->guard->isLastActiveSuperAdmin($user)) {
            throw ValidationException::withMessages(['roles' => __('users.flash.cannot_remove_last_admin_role')]);
        }

        $user->syncRoles($new);

        $this->audit->log('user.roles_assigned', $user, ['name' => $user->translations('name')], ['roles' => $old], ['roles' => $new]);
    }

    /**
     * @param  list<int>|null  $branchIds
     */
    private function syncBranches(User $user, ?array $branchIds, ?bool $allBranches): void
    {
        $oldIds = $user->branches()->pluck('branches.id')->sort()->values()->all();
        $oldAll = $user->hasDirectPermission(self::ALL_BRANCHES_PERMISSION);

        $newIds = $branchIds === null ? $oldIds : collect($branchIds)->map(fn ($id): int => (int) $id)->unique()->sort()->values()->all();
        $newAll = $allBranches ?? $oldAll;

        if ($oldIds === $newIds && $oldAll === $newAll) {
            return;
        }

        $user->branches()->sync($newIds);

        if ($newAll !== $oldAll) {
            $newAll
                ? $user->givePermissionTo(self::ALL_BRANCHES_PERMISSION)
                : $user->revokePermissionTo(self::ALL_BRANCHES_PERMISSION);
        }

        $codes = fn (array $ids): array => Branch::query()->whereKey($ids)->orderBy('code')->pluck('code')->all();

        $this->audit->log(
            'user.branches_assigned',
            $user,
            ['name' => $user->translations('name')],
            ['branches' => $codes($oldIds), 'all_branches' => $oldAll],
            ['branches' => $codes($newIds), 'all_branches' => $newAll],
        );
    }
}

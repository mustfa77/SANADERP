<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the default roles from config/permissions.php with names from
 * lang/{ar,en}/roles.php.
 *
 * Default permissions are applied only when a role is first created, so
 * re-running the seeder never overwrites changes made from the UI. The
 * Super Admin role is the exception: it always receives every permission.
 */
class RoleSeeder extends Seeder
{
    public function run(PermissionRegistry $registry): void
    {
        $guard = config('auth.defaults.guard');
        $superAdminRole = config('permissions.super_admin_role');

        foreach (config('permissions.roles') as $key => $definition) {
            $role = Role::query()->updateOrCreate(
                ['name' => $key, 'guard_name' => $guard],
                [
                    'name_ar' => __("roles.defaults.{$key}.name", [], 'ar'),
                    'name_en' => __("roles.defaults.{$key}.name", [], 'en'),
                    'description_ar' => __("roles.defaults.{$key}.description", [], 'ar'),
                    'description_en' => __("roles.defaults.{$key}.description", [], 'en'),
                    'is_system' => $definition['is_system'],
                ],
            );

            if ($role->wasRecentlyCreated || $key === $superAdminRole) {
                $role->syncPermissions(
                    $registry->resolve($definition['permissions'], $definition['except'] ?? []),
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

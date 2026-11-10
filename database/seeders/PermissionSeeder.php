<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs the permissions table with config/permissions.php. Safe to re-run:
 * existing permissions are updated, missing ones created and permissions no
 * longer in the catalogue are removed.
 */
class PermissionSeeder extends Seeder
{
    public function run(PermissionRegistry $registry): void
    {
        $guard = config('auth.defaults.guard');

        foreach ($registry->all() as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $guard],
                [
                    'label_ar' => $registry->label($permission['name'], 'ar'),
                    'label_en' => $registry->label($permission['name'], 'en'),
                    'group' => $permission['group'],
                    'sort_order' => $permission['sort_order'],
                ],
            );
        }

        Permission::query()
            ->where('guard_name', $guard)
            ->whereNotIn('name', $registry->names())
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

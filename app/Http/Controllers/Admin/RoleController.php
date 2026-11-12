<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private const AUDITED_FIELDS = ['name', 'name_ar', 'name_en', 'description_ar', 'description_en', 'requires_2fa'];

    public function __construct(
        private AuditLogger $audit,
        private PermissionRegistrar $registrar,
    ) {}

    public function index(): View
    {
        $roles = Role::query()->withCount(['permissions', 'users'])->orderBy('id')->get();

        return view('admin.roles.index', ['roles' => $roles]);
    }

    public function create(): View
    {
        return view('admin.roles.create', ['role' => new Role]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::query()->create([
            ...$request->validated(),
            'guard_name' => config('auth.defaults.guard'),
        ]);

        $this->audit->log('role.created', $role, ['role' => $role->translations('name')], new: $role->only(self::AUDITED_FIELDS));

        return redirect()->route('admin.roles.permissions.edit', $role)->with('success', __('roles.flash.created'));
    }

    public function edit(Role $role): View
    {
        $role->loadCount('users');

        return view('admin.roles.edit', ['role' => $role]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $old = $role->only(self::AUDITED_FIELDS);
        $role->update($request->validated());
        $changes = $role->getChanges();
        unset($changes['updated_at']);

        if ($changes !== []) {
            $this->audit->log(
                'role.updated',
                $role,
                ['role' => $role->translations('name')],
                array_intersect_key($old, $changes),
                $changes,
            );
        }

        $this->registrar->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', __('roles.flash.updated'));
    }

    /**
     * Refuses to delete a role still assigned to users and names them.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $users = User::role($role->name)->limit(10)->get();

        if ($users->isNotEmpty()) {
            return back()->with('error', __('roles.flash.in_use', [
                'users' => $users->map->displayName()->implode('، '),
            ]));
        }

        $permissions = $role->permissions()->pluck('name')->all();
        $role->delete();
        $this->registrar->forgetCachedPermissions();

        $this->audit->log('role.deleted', $role, ['role' => $role->translations('name')], ['permissions' => $permissions]);

        return redirect()->route('admin.roles.index')->with('success', __('roles.flash.deleted'));
    }

    public function clone(Role $role): RedirectResponse
    {
        $copy = DB::transaction(function () use ($role): Role {
            $copy = Role::query()->create([
                'name' => $this->uniqueKey($role->name.'_copy'),
                'guard_name' => $role->guard_name,
                'name_ar' => __('roles.copy_of', ['name' => $role->name_ar], 'ar'),
                'name_en' => __('roles.copy_of', ['name' => $role->name_en], 'en'),
                'description_ar' => $role->description_ar,
                'description_en' => $role->description_en,
                'requires_2fa' => $role->requires_2fa,
                'is_system' => false,
            ]);

            $copy->syncPermissions($role->permissions);

            $this->audit->log('role.cloned', $copy, [
                'role' => $copy->translations('name'),
                'source' => $role->translations('name'),
            ], new: ['permissions' => $copy->permissions()->pluck('name')->sort()->values()->all()]);

            return $copy;
        });

        return redirect()->route('admin.roles.edit', $copy)->with('success', __('roles.flash.cloned'));
    }

    public function editPermissions(Role $role, PermissionRegistry $registry): View
    {
        return view('admin.roles.permissions', [
            'role' => $role,
            'sections' => $registry->sections(),
            'actions' => config('permissions.standard_actions'),
            'granted' => $role->isSuperAdmin()
                ? $registry->names()
                : $role->permissions()->pluck('name')->all(),
            'readOnly' => ! auth()->user()->can('updatePermissions', $role),
        ]);
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $old = $role->permissions()->pluck('name')->sort()->values()->all();
        $new = collect($request->validated('permissions', []))->unique()->sort()->values()->all();

        if ($old !== $new) {
            $role->syncPermissions($new);
            $this->registrar->forgetCachedPermissions();

            $this->audit->log('role.permissions_changed', $role, [
                'role' => $role->translations('name'),
                'added' => count(array_diff($new, $old)),
                'removed' => count(array_diff($old, $new)),
            ], ['permissions' => $old], ['permissions' => $new]);
        }

        return redirect()->route('admin.roles.permissions.edit', $role)->with('success', __('roles.flash.permissions_updated'));
    }

    private function uniqueKey(string $base): string
    {
        $key = Str::limit($base, 90, '');
        $suffix = 1;

        while (Role::query()->where('name', $key)->exists()) {
            $key = Str::limit($base, 90, '').'_'.++$suffix;
        }

        return $key;
    }
}

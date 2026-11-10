<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_returns_403_without_roles_permission(): void
    {
        $user = $this->userWithRole('rental_team');
        $role = Role::findByName('accounts');

        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.roles.permissions.update', $role), ['permissions' => ['users.view']])->assertForbidden();
    }

    public function test_permission_change_is_saved_and_audited_with_before_and_after(): void
    {
        $admin = $this->userWithRole('super_admin');
        $role = Role::findByName('marketing_team');
        $before = $role->permissions()->pluck('name')->sort()->values()->all();

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions.update', $role), ['permissions' => ['dashboard.view', 'candidates.approve']])
            ->assertRedirect(route('admin.roles.permissions.edit', $role));

        $this->assertEqualsCanonicalizing(['dashboard.view', 'candidates.approve'], $role->fresh()->permissions->pluck('name')->all());

        $log = AuditLog::query()->where('action_key', 'role.permissions_changed')->sole();
        $this->assertSame(['permissions' => $before], $log->old_values);
        $this->assertSame(['permissions' => ['candidates.approve', 'dashboard.view']], $log->new_values);
    }

    public function test_rejects_permissions_not_in_the_catalogue(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions.update', Role::findByName('accounts')), ['permissions' => ['made.up']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_super_admin_permissions_cannot_be_reduced(): void
    {
        $admin = $this->userWithRole('super_admin');
        $superAdminRole = Role::findByName('super_admin');
        $count = $superAdminRole->permissions()->count();

        $this->actingAs($admin)
            ->put(route('admin.roles.permissions.update', $superAdminRole), ['permissions' => ['dashboard.view']])
            ->assertForbidden();

        $this->assertSame($count, $superAdminRole->permissions()->count());
    }

    public function test_role_assigned_to_users_cannot_be_deleted_and_lists_them(): void
    {
        $admin = $this->userWithRole('super_admin');
        $member = $this->userWithRole('rental_team', attributes: ['name_ar' => 'خالد المؤجر']);
        app()->setLocale('ar');

        $this->actingAs($admin)
            ->from(route('admin.roles.index'))
            ->delete(route('admin.roles.destroy', Role::findByName('rental_team')))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', __('roles.flash.in_use', ['users' => $member->name_ar]));

        $this->assertNotNull(Role::query()->where('name', 'rental_team')->first());
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->delete(route('admin.roles.destroy', Role::findByName('driver')))->assertForbidden();
    }

    public function test_clone_copies_permissions_into_new_role(): void
    {
        $admin = $this->userWithRole('super_admin');
        $source = Role::findByName('accounts');

        $this->actingAs($admin)->post(route('admin.roles.clone', $source))->assertRedirect();

        $copy = Role::query()->where('name', 'accounts_copy')->sole();
        $this->assertFalse($copy->is_system);
        $this->assertEqualsCanonicalizing($source->permissions->pluck('name')->all(), $copy->permissions->pluck('name')->all());
    }

    public function test_matrix_labels_follow_the_selected_language(): void
    {
        $admin = $this->userWithRole('super_admin');
        $role = Role::findByName('accounts');

        $this->actingAs($admin)->withSession(['locale' => 'ar'])
            ->get(route('admin.roles.permissions.edit', $role))
            ->assertSee('الحسابات')
            ->assertSee('إصدار الفاتورة في وافق')
            ->assertSee(__('permissions.sections.finance', [], 'ar'));

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.roles.permissions.edit', $role))
            ->assertSee('Accounts')
            ->assertSee('Issue invoice in Wafeq')
            ->assertSee('Finance');
    }
}

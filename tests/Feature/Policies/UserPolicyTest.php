<?php

namespace Tests\Feature\Policies;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_last_active_super_admin_cannot_be_deactivated_or_deleted(): void
    {
        $lastAdmin = $this->userWithRole('super_admin');
        $inactiveAdmin = $this->userWithRole('super_admin', attributes: ['status' => 'inactive']);

        $this->assertFalse(Gate::forUser($inactiveAdmin)->allows('deactivate', $lastAdmin));
        $this->assertFalse(Gate::forUser($inactiveAdmin)->allows('delete', $lastAdmin));
    }

    public function test_super_admin_can_be_deactivated_when_another_active_super_admin_exists(): void
    {
        $admin = $this->userWithRole('super_admin');
        $secondAdmin = $this->userWithRole('super_admin');

        $this->assertTrue(Gate::forUser($admin)->allows('deactivate', $secondAdmin));
    }

    public function test_nobody_can_deactivate_delete_or_reassign_themselves(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->userWithRole('super_admin');

        $this->assertFalse(Gate::forUser($admin)->allows('deactivate', $admin));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
        $this->assertFalse(Gate::forUser($admin)->allows('assignRoles', $admin));
    }

    public function test_non_super_admin_cannot_manage_a_super_admin(): void
    {
        $manager = $this->userWithRole('general_manager');
        $manager->givePermissionTo(['users.edit', 'users.delete']);
        $admin = $this->userWithRole('super_admin');
        $staff = $this->userWithRole('rental_team');

        $this->assertFalse(Gate::forUser($manager)->allows('update', $admin));
        $this->assertFalse(Gate::forUser($manager)->allows('deactivate', $admin));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $staff));
        $this->assertTrue(Gate::forUser($manager)->allows('deactivate', $staff));
    }

    public function test_deactivating_last_super_admin_over_http_returns_403(): void
    {
        $lastAdmin = $this->userWithRole('super_admin');

        $this->actingAs($lastAdmin)
            ->patch(route('admin.users.deactivate', $lastAdmin))
            ->assertForbidden();

        $this->assertTrue($lastAdmin->fresh()->isActive());
    }
}

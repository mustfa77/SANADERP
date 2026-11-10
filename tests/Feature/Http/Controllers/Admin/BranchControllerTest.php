<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'code' => 'jed',
            'name_ar' => 'جدة',
            'name_en' => 'Jeddah',
            'city_ar' => 'جدة',
            'city_en' => 'Jeddah',
            'phone' => '0126543210',
            'email' => 'Jeddah@Example.com',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_returns_403_without_branches_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.branches.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.branches.store'), $this->payload())->assertForbidden();
    }

    public function test_lists_and_searches_branches(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.branches.index', ['search' => 'Qass']))
            ->assertOk()
            ->assertSee(route('admin.branches.show', $this->branch('QSM')))
            ->assertDontSee(route('admin.branches.show', $this->branch('RYD')));
    }

    public function test_creates_branch_with_normalized_code_and_audits_it(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.branches.store'), $this->payload(['manager_id' => $admin->id]))
            ->assertRedirect();

        $branch = Branch::query()->where('code', 'JED')->sole();
        $this->assertSame('jeddah@example.com', $branch->email);
        $this->assertTrue($branch->manager->is($admin));
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'branch.created', 'target_id' => $branch->id]);
    }

    public function test_rejects_duplicate_code_with_branch_labels(): void
    {
        app()->setLocale('ar');
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->post(route('admin.branches.store'), $this->payload(['code' => 'ryd']))
            ->assertSessionHasErrors(['code' => 'قيمة رمز الفرع مستخدمة من قبل.']);
    }

    public function test_user_cannot_open_another_branch(): void
    {
        $manager = $this->userWithRole('rental_team', ['RYD']);
        $manager->givePermissionTo(['branches.view', 'branches.edit']);

        $this->actingAs($manager)->get(route('admin.branches.show', $this->branch('RYD')))->assertOk();
        $this->actingAs($manager)->get(route('admin.branches.show', $this->branch('QSM')))->assertNotFound();
        $this->actingAs($manager)->put(route('admin.branches.update', $this->branch('QSM')), $this->payload())->assertNotFound();

        $this->actingAs($manager)->get(route('admin.branches.index'))
            ->assertSee(route('admin.branches.show', $this->branch('RYD')))
            ->assertDontSee(route('admin.branches.show', $this->branch('QSM')));
    }

    public function test_update_logs_only_changed_fields(): void
    {
        $admin = $this->userWithRole('super_admin');
        $branch = $this->branch('QSM');

        $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
            ...$branch->only(['code', 'name_ar', 'name_en', 'city_ar', 'city_en']),
            'phone' => '0163334444',
            'is_active' => '1',
        ])->assertRedirect(route('admin.branches.show', $branch));

        $log = AuditLog::query()->where('action_key', 'branch.updated')->sole();
        $this->assertSame(['phone' => null], $log->old_values);
        $this->assertSame(['phone' => '0163334444'], $log->new_values);
    }

    public function test_cannot_delete_branch_with_users_or_workers(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->userWithRole('rental_team', ['QSM']);
        Worker::factory()->create(['branch_id' => $this->branch('QSM')->id]);

        $this->actingAs($admin)->delete(route('admin.branches.destroy', $this->branch('QSM')))
            ->assertSessionHas('error', __('branches.flash.in_use', ['users' => 1, 'workers' => 1, 'trips' => 0]));

        $this->assertNotSoftDeleted($this->branch('QSM'));
    }

    public function test_deletes_empty_branch(): void
    {
        $admin = $this->userWithRole('super_admin');
        $branch = Branch::factory()->create();

        $this->actingAs($admin)->delete(route('admin.branches.destroy', $branch))->assertRedirect(route('admin.branches.index'));

        $this->assertSoftDeleted($branch);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'branch.deleted', 'target_id' => $branch->id]);
    }

    public function test_cannot_deactivate_last_active_branch(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->patch(route('admin.branches.deactivate', $this->branch('QSM')))->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.branches.deactivate', $this->branch('RYD')))
            ->assertSessionHas('error', __('branches.flash.last_active'));

        $this->assertTrue($this->branch('RYD')->is_active);
        $this->assertFalse($this->branch('QSM')->is_active);
    }

    public function test_inactive_branch_is_hidden_from_switcher(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->branch('QSM')->update(['is_active' => false]);

        $this->actingAs($admin)->withSession(['locale' => 'en'])->get(route('admin.dashboard'))
            ->assertSee('Riyadh')
            ->assertDontSee('Qassim');
    }

    public function test_exports_branches_to_excel(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->get(route('admin.branches.export'))
            ->assertOk()
            ->assertDownload('branches-'.now()->format('Y-m-d').'.xlsx');
    }
}

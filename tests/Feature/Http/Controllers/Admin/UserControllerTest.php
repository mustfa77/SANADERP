<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_returns_403_without_users_view_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_hides_users_menu_item_without_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'))
            ->assertSee(__('permissions.modules.rental_contracts'));
    }

    public function test_index_searches_by_name_and_filters_by_role(): void
    {
        $admin = $this->userWithRole('super_admin');
        $this->userWithRole('rental_team', attributes: ['name_en' => 'Rental Person']);
        $this->userWithRole('accounts', attributes: ['name_en' => 'Accounts Person']);

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.users.index', ['search' => 'Rental']))
            ->assertSee('Rental Person')
            ->assertDontSee('Accounts Person');

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('admin.users.index', ['role' => 'accounts']))
            ->assertSee('Accounts Person')
            ->assertDontSee('Rental Person');
    }

    public function test_creates_user_with_roles_and_branches_and_audits_it(): void
    {
        $admin = $this->userWithRole('super_admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name_ar' => 'سارة',
            'name_en' => 'Sara',
            'email' => 'Sara@Example.com',
            'phone' => '0551112233',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'user_type' => 'internal',
            'preferred_language' => 'en',
            'must_change_password' => '1',
            'access_submitted' => '1',
            'roles' => ['rental_team'],
            'branches' => [$this->branch('QSM')->id],
            'all_branches' => '0',
        ]);

        $user = User::query()->where('email', 'sara@example.com')->firstOrFail();
        $response->assertRedirect(route('admin.users.show', $user));

        $this->assertTrue($user->hasRole('rental_team'));
        $this->assertSame([$this->branch('QSM')->id], $user->branches()->pluck('branches.id')->all());
        $this->assertTrue($user->must_change_password);
        $this->assertSame($admin->id, $user->created_by);

        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'user.created', 'target_id' => $user->id]);
        $rolesLog = AuditLog::query()->where('action_key', 'user.roles_assigned')->sole();
        $this->assertSame(['roles' => []], $rolesLog->old_values);
        $this->assertSame(['roles' => ['rental_team']], $rolesLog->new_values);
    }

    public function test_rejects_invalid_user_payload_with_translated_messages(): void
    {
        app()->setLocale('ar');
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), ['phone' => '123', 'user_type' => 'external_agency'])
            ->assertSessionHasErrors([
                'name_ar' => 'حقل الاسم بالعربي مطلوب.',
                'phone' => 'رقم الجوال يجب أن يكون بالصيغة 05XXXXXXXX.',
                'agency_id' => 'حقل المكتب الخارجي مطلوب عندما يكون نوع المستخدم هو مكتب خارجي.',
            ]);
    }

    public function test_user_cannot_change_own_roles(): void
    {
        $manager = $this->userWithRole('general_manager');
        $manager->givePermissionTo('users.edit');

        $this->actingAs($manager)
            ->put(route('admin.users.update', $manager), [
                'name_ar' => $manager->name_ar,
                'name_en' => $manager->name_en,
                'email' => $manager->email,
                'phone' => $manager->phone,
                'user_type' => 'internal',
                'preferred_language' => 'ar',
                'roles' => ['super_admin'],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertFalse($manager->fresh()->hasRole('super_admin'));
        $this->assertFalse(auth()->user()->can('assignRoles', $manager));
    }

    public function test_cannot_remove_super_admin_role_from_last_active_super_admin(): void
    {
        $admin = $this->userWithRole('super_admin');
        $otherAdmin = $this->userWithRole('super_admin', attributes: ['status' => 'inactive']);

        // The active admin edits the inactive one: allowed.
        $this->actingAs($admin)->put(route('admin.users.update', $otherAdmin), [
            ...$otherAdmin->only(['name_ar', 'name_en', 'email', 'phone']),
            'user_type' => 'internal',
            'preferred_language' => 'ar',
            'access_submitted' => '1',
            'roles' => [],
        ])->assertRedirect();

        $this->assertFalse($otherAdmin->fresh()->hasRole('super_admin'));
        $this->assertTrue($admin->fresh()->hasRole('super_admin'));
    }

    public function test_shows_masked_activity_and_tabs_for_a_user(): void
    {
        $admin = $this->userWithRole('super_admin');
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee($user->name_ar)
            ->assertSee(__('users.tabs.activity'))
            ->assertSee(__('roles.defaults.rental_team.name'));
    }
}

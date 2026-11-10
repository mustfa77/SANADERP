<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\Role;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_login_page_renders_in_arabic_by_default(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('auth.login.submit', [], 'ar'));
    }

    public function test_valid_credentials_log_in_and_redirect_to_dashboard(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Password1'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas(LoginAttempt::class, ['user_id' => $user->id, 'successful' => true]);
        $this->assertDatabaseHas(AuditLog::class, ['user_id' => $user->id, 'action_key' => 'auth.login']);
    }

    public function test_wrong_password_is_rejected_and_logged(): void
    {
        $user = $this->userWithRole('rental_team');

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
        $this->assertDatabaseHas(LoginAttempt::class, [
            'user_id' => $user->id, 'successful' => false, 'failure_reason' => 'invalid_credentials',
        ]);
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'auth.failed', 'target_id' => $user->id]);
    }

    public function test_locks_login_for_15_minutes_after_5_failed_attempts(): void
    {
        $user = $this->userWithRole('rental_team');

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        // Even the correct password is refused while locked out.
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Password1'])
            ->assertSessionHasErrors(['email' => __('auth.throttle', ['seconds' => 900, 'minutes' => 15])]);

        $this->assertGuest();
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'auth.lockout', 'target_id' => $user->id]);
        $this->assertDatabaseHas(LoginAttempt::class, ['user_id' => $user->id, 'failure_reason' => 'locked_out']);

        $this->travel(16)->minutes();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Password1'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Password1'])
            ->assertSessionHasErrors(['email' => __('auth.inactive')]);

        $this->assertGuest();
    }

    public function test_external_agency_user_lands_on_agency_portal(): void
    {
        $user = User::factory()->externalAgency()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Password1'])
            ->assertRedirect(route('agency.candidates.index'));
    }

    public function test_role_requiring_2fa_asks_for_emailed_code_before_dashboard(): void
    {
        Notification::fake();
        $user = $this->userWithRole('rental_team');
        Role::findByName('rental_team')->update(['requires_2fa' => true]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Password1'])
            ->assertRedirect(route('two-factor.show'));

        $this->get(route('admin.dashboard'))->assertRedirect(route('two-factor.show'));

        $code = null;
        Notification::assertSentTo($user, TwoFactorCode::class, function ($notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    }
}

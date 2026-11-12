<?php

namespace Tests\Feature\Http\Middleware;

use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $this->seedAccess();
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();

        $user->forceFill(['status' => UserStatus::Inactive])->save();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.inactive')]);

        $this->assertGuest();
    }

    public function test_deactivating_from_admin_logs_the_user_out(): void
    {
        $this->seedAccess();
        $admin = $this->userWithRole('super_admin');
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($admin)
            ->patch(route('admin.users.deactivate', $user))
            ->assertRedirect();

        $this->assertSame(UserStatus::Inactive, $user->fresh()->status);

        $this->actingAs($user->fresh())->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

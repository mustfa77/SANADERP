<?php

namespace Tests\Feature\Http\Controllers\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    public function test_shows_system_sections_with_financials_for_authorized_user(): void
    {
        $admin = $this->userWithRole('super_admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.demo_notice'))
            ->assertSee(__('dashboard.kpis.workers_on_duty'))
            ->assertSee(__('dashboard.recruitment_pipeline'))
            ->assertSee(__('dashboard.alerts.iqama_expiring.title'))
            ->assertSee(__('dashboard.latest_contracts'))
            ->assertSee(__('dashboard.kpis.month_revenue'))
            ->assertSee(__('dashboard.table.amount'));
    }

    public function test_hides_financial_figures_without_permission(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.kpis.active_rentals'))
            ->assertDontSee(__('dashboard.kpis.month_revenue'))
            ->assertDontSee(__('dashboard.table.amount'));
    }

    public function test_branch_section_lists_only_the_users_branches(): void
    {
        $user = $this->userWithRole('rental_team', ['RYD']);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.dashboard'))
            ->assertSee('Riyadh')
            ->assertDontSee('Qassim');
    }
}

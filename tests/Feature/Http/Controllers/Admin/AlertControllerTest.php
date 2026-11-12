<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\AlertType;
use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\Alert;
use App\Models\Camp;
use App\Models\Setting;
use App\Models\Worker;
use App\Services\Alerts\AlertService;
use App\Services\Camps\CampService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AlertControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
        Setting::put('days_before_iqama_expiry', '30');
    }

    private function worker(array $attributes = [], string $branch = 'RYD'): Worker
    {
        $worker = Worker::factory()->create(['branch_id' => $this->branch($branch)->id, 'sponsor_type' => SponsorType::Company, ...$attributes]);

        return app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);
    }

    public function test_refresh_creates_alerts_and_closes_them_when_the_cause_is_gone(): void
    {
        $soon = $this->worker(['name_en' => 'Soon Expiry', 'iqama_no' => '2123456789', 'iqama_expiry' => today()->addDays(10)]);
        $this->worker(['iqama_no' => '2123456780', 'iqama_expiry' => today()->addDays(200)]);

        Artisan::call('alerts:refresh');

        $alert = Alert::query()->withoutGlobalScopes()->sole();
        $this->assertSame(AlertType::IqamaExpiry, $alert->type);
        $this->assertNull($alert->resolved_at);

        // Running again does not duplicate.
        app(AlertService::class)->refresh();
        $this->assertSame(1, Alert::query()->withoutGlobalScopes()->count());

        // Iqama renewed: the alert closes by itself.
        $soon->forceFill(['iqama_expiry' => today()->addYear()])->save();
        app(AlertService::class)->refresh();
        $this->assertNotNull($alert->fresh()->resolved_at);
        $this->assertNull($alert->fresh()->resolved_by);
    }

    public function test_bell_and_page_show_alerts_of_the_users_branches_and_permitted_types(): void
    {
        $this->worker(['name_en' => 'Riyadh Worker', 'iqama_no' => '2123456789', 'iqama_expiry' => today()->addDays(5)]);
        $this->worker(['name_en' => 'Qassim Worker', 'iqama_no' => '2123456788', 'iqama_expiry' => today()->addDays(5)], 'QSM');

        // An absence in a housing (camps.view).
        $camp = Camp::factory()->create(['branch_id' => $this->branch('RYD')->id]);
        $absent = $this->worker(['name_en' => 'Absent Ana', 'camp_id' => $camp->id]);
        app(CampService::class)->recordAttendance($camp, today(), [$absent->id => ['status' => 'absent']]);

        app(AlertService::class)->refresh();

        $manager = $this->userWithRole('branch_manager', ['RYD']);
        $this->actingAs($manager)->withSession(['locale' => 'en'])->get(route('admin.alerts.index'))
            ->assertOk()
            ->assertSee('Riyadh Worker')
            ->assertSee('Absent Ana')
            ->assertDontSee('Qassim Worker');

        // The bell shows on every page.
        $this->actingAs($manager)->get(route('admin.workers.index'))->assertSee('alerts-bell', false);

        // The rental team has no alerts.view.
        $this->actingAs($this->userWithRole('rental_team', ['RYD']))->get(route('admin.alerts.index'))->assertForbidden();
    }

    public function test_an_alert_marked_as_handled_does_not_come_back_for_the_same_date(): void
    {
        $this->worker(['iqama_no' => '2123456789', 'iqama_expiry' => today()->addDays(5)]);
        app(AlertService::class)->refresh();
        $alert = Alert::query()->withoutGlobalScopes()->sole();
        $manager = $this->userWithRole('branch_manager', ['RYD']);

        $this->actingAs($manager)->post(route('admin.alerts.resolve', $alert))->assertSessionHas('success');
        $this->assertSame($manager->id, $alert->fresh()->resolved_by);

        app(AlertService::class)->refresh();
        $this->assertNotNull($alert->fresh()->resolved_at);
        $this->assertSame(1, Alert::query()->withoutGlobalScopes()->count());
    }
}

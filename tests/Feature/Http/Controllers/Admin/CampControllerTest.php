<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\CampMovementType;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\AuditLog;
use App\Models\Camp;
use App\Models\CampAttendance;
use App\Models\CampMovement;
use App\Models\Client;
use App\Models\Country;
use App\Models\Price;
use App\Models\User;
use App\Models\Worker;
use App\Services\Rental\RentalContractService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampControllerTest extends TestCase
{
    use RefreshDatabase;

    private Camp $camp;

    private Camp $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
        $this->camp = Camp::factory()->create(['branch_id' => $this->branch('RYD')->id, 'name_en' => 'North Housing', 'capacity' => 2]);
        $this->other = Camp::factory()->create(['branch_id' => $this->branch('RYD')->id, 'name_en' => 'South Housing', 'capacity' => 10]);
    }

    private function worker(array $attributes = [], string $branch = 'RYD'): Worker
    {
        $worker = Worker::factory()->create([
            'branch_id' => $this->branch($branch)->id,
            'sponsor_type' => SponsorType::Company,
            ...$attributes,
        ]);

        return app(WorkerStatusService::class)->initialize($worker, WorkerStatus::Available);
    }

    private function campTeam(): User
    {
        return $this->userWithRole('camp_team', ['RYD']);
    }

    public function test_check_in_transfer_and_check_out_are_logged_with_the_reason(): void
    {
        $user = $this->campTeam();
        $worker = $this->worker(['name_en' => 'Moving Mary']);

        $this->actingAs($user)->post(route('admin.camps.check-in', $this->camp), ['worker_id' => $worker->id, 'note' => 'Arrived from airport'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('admin.camps.move', [$this->camp, $worker]), ['to_camp_id' => $this->other->id, 'note' => 'Room change'])->assertSessionHas('success');
        $this->actingAs($user)->post(route('admin.camps.move', [$this->other, $worker]), ['note' => 'Hospital'])->assertSessionHas('success');

        $this->assertNull($worker->fresh()->camp_id);
        $this->assertSame(
            [[CampMovementType::In, 'Arrived from airport'], [CampMovementType::Transfer, 'Room change'], [CampMovementType::Out, 'Hospital']],
            CampMovement::query()->where('worker_id', $worker->id)->orderBy('id')->get()->map(fn ($movement) => [$movement->type, $movement->note])->all(),
        );
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'camp.moved', 'target_id' => $worker->id]);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.camps.movements'))->assertOk()->assertSee('Room change');
        $this->actingAs($this->userWithRole('super_admin'))->withSession(['locale' => 'en'])->get(route('admin.workers.show', [$worker, 'tab' => 'camp']))->assertOk()->assertSee('Hospital');
    }

    public function test_movements_from_other_modules_are_logged_automatically(): void
    {
        $country = Country::factory()->create();
        Price::factory()->create(['country_id' => $country->id]);
        $worker = $this->worker(['country_id' => $country->id, 'rental_type' => WorkerRentalType::Monthly, 'camp_id' => null]);
        $worker->forceFill(['camp_id' => $this->camp->id])->save();

        app(RentalContractService::class)->create([
            'client_id' => Client::factory()->create(['branch_id' => $worker->branch_id])->id,
            'worker_id' => $worker->id,
            'starts_at' => today()->toDateString(),
        ]);

        $out = CampMovement::query()->where('worker_id', $worker->id)->latest('id')->first();
        $this->assertSame(CampMovementType::Out, $out->type);
        $this->assertSame($this->camp->id, $out->from_camp_id);
        $this->assertStringContainsString('MR-', (string) $out->note);
    }

    public function test_capacity_status_and_branch_rules(): void
    {
        $user = $this->campTeam();
        $this->worker(['camp_id' => $this->camp->id]);
        $this->worker(['camp_id' => $this->camp->id]);
        $third = $this->worker();

        // Full housing.
        $this->actingAs($user)->post(route('admin.camps.check-in', $this->camp), ['worker_id' => $third->id, 'note' => 'x'])->assertSessionHas('error');

        // A worker with a client cannot be housed; another branch is a 404.
        $rented = $this->worker();
        app(WorkerStatusService::class)->transition($rented, WorkerStatus::RentedMonthly);
        $this->actingAs($user)->post(route('admin.camps.check-in', $this->other), ['worker_id' => $rented->id, 'note' => 'x'])->assertNotFound();

        $qassim = Camp::factory()->create(['branch_id' => $this->branch('QSM')->id]);
        $this->actingAs($user)->get(route('admin.camps.show', $qassim))->assertNotFound();

        // Reading needs camps.view, moving needs camp_movements.create.
        $viewer = $this->userWithRole('rental_team', ['RYD']);
        $this->actingAs($viewer)->get(route('admin.camps.index'))->assertForbidden();
    }

    public function test_daily_roll_call_is_saved_per_worker_and_shown_on_the_overview(): void
    {
        $user = $this->campTeam();
        $present = $this->worker(['camp_id' => $this->other->id, 'name_en' => 'Present Pia']);
        $absent = $this->worker(['camp_id' => $this->other->id, 'name_en' => 'Absent Ana']);
        $elsewhere = $this->worker(['camp_id' => $this->camp->id]);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.camps.show', [$this->other, 'tab' => 'attendance']))
            ->assertOk()->assertSee('Present Pia')->assertSee('Absent Ana');

        $this->actingAs($user)->post(route('admin.camps.attendance', $this->other), [
            'date' => today()->toDateString(),
            'rows' => [
                $present->id => ['status' => 'present'],
                $absent->id => ['status' => 'absent', 'note' => 'Not in room'],
                $elsewhere->id => ['status' => 'present'],   // not in this housing: ignored
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(2, CampAttendance::query()->count());
        $this->assertSame(AttendanceStatus::Absent, CampAttendance::query()->where('worker_id', $absent->id)->sole()->status);

        // Saving again updates the same day.
        $this->actingAs($user)->post(route('admin.camps.attendance', $this->other), [
            'date' => today()->toDateString(), 'rows' => [$absent->id => ['status' => 'sick']],
        ]);
        $this->assertSame(2, CampAttendance::query()->count());

        $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('admin.camps.index'))
            ->assertOk()->assertSee('South Housing')->assertSee(__('camps.attendance_statuses.sick', [], 'en'));
    }
}

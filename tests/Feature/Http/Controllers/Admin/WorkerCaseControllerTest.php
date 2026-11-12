<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\CaseDecision;
use App\Enums\CaseStatus;
use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\AuditLog;
use App\Models\Camp;
use App\Models\Client;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerCase;
use App\Services\Cases\WorkerCaseService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerCaseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccess();
    }

    /**
     * A company worker sent to "awaiting decision" (opens a case).
     */
    private function awaitingWorker(string $branch = 'RYD'): Worker
    {
        $worker = Worker::factory()->create(['branch_id' => $this->branch($branch)->id, 'sponsor_type' => SponsorType::Company]);
        $statuses = app(WorkerStatusService::class);
        $statuses->initialize($worker, WorkerStatus::Available);

        return $statuses->transition($worker, WorkerStatus::AwaitingDecision, 'Returned by the client: not suitable');
    }

    /**
     * Decides cases (cases.decide; sees every branch).
     */
    private function manager(): User
    {
        return $this->userWithRole('general_manager', ['RYD']);
    }

    /**
     * Follows up and completes cases of the branch (cases.* without decide).
     */
    private function branchManager(): User
    {
        return $this->userWithRole('branch_manager', ['RYD']);
    }

    public function test_a_case_opens_automatically_once_per_worker(): void
    {
        $worker = $this->awaitingWorker();

        $case = WorkerCase::query()->sole();
        $this->assertSame(CaseStatus::Open, $case->status);
        $this->assertSame('Returned by the client: not suitable', $case->reason);
        $this->assertSame('CS-'.now()->format('Y').'-0001', $case->number);

        // Another awaiting_decision event does not open a second case.
        app(WorkerCaseService::class)->open($worker);
        $this->assertSame(1, WorkerCase::query()->count());
        $this->assertDatabaseHas(AuditLog::class, ['action_key' => 'case.opened', 'target_id' => $case->id]);
    }

    public function test_back_to_work_closes_the_case_and_makes_the_worker_available(): void
    {
        $worker = $this->awaitingWorker();
        $case = WorkerCase::query()->sole();

        $this->actingAs($this->manager())->withSession(['locale' => 'en'])->get(route('admin.cases.show', $case))->assertOk()->assertSee(route('admin.cases.decide', $case), false);
        $this->actingAs($this->manager())->post(route('admin.cases.decide', $case), ['decision' => 'return_to_work', 'decision_note' => 'Good worker'])->assertSessionHas('success');

        $this->assertSame(CaseStatus::Closed, $case->fresh()->status);
        $this->assertSame(WorkerStatus::Available, $worker->fresh()->status);
    }

    public function test_deportation_is_decided_then_completed(): void
    {
        $camp = Camp::factory()->create(['branch_id' => $this->branch('RYD')->id]);
        $worker = $this->awaitingWorker();
        $worker->forceFill(['camp_id' => $camp->id])->save();
        $case = WorkerCase::query()->sole();
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('admin.cases.decide', $case), [
            'decision' => 'deportation', 'flight_date' => today()->addDays(5)->toDateString(), 'ticket_cost' => 1800,
        ])->assertSessionHas('success');
        $this->assertSame(CaseStatus::InProgress, $case->fresh()->status);
        $this->assertSame(WorkerStatus::ForDeportation, $worker->fresh()->status);

        // The branch manager follows up and completes (cannot decide).
        $branchManager = $this->branchManager();
        $this->actingAs($branchManager)->post(route('admin.cases.note', $case), ['note' => 'Exit visa issued'])->assertSessionHas('success');
        $this->actingAs($branchManager)->post(route('admin.cases.complete', $case), ['completed_at' => today()->toDateString()])->assertSessionHas('success');

        $case->refresh();
        $this->assertSame(CaseStatus::Closed, $case->status);
        $this->assertSame('1800.00', $case->ticket_cost);
        $this->assertSame(WorkerStatus::Deported, $worker->fresh()->status);
        $this->assertNull($worker->fresh()->camp_id);
        $this->assertSame(1, $case->notes()->count());
    }

    public function test_transfer_needs_the_new_sponsor(): void
    {
        $worker = $this->awaitingWorker();
        $case = WorkerCase::query()->sole();
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('admin.cases.decide', $case), ['decision' => 'transfer'])->assertSessionHas('error');
        $this->assertSame(CaseStatus::Open, $case->fresh()->status);

        $client = Client::factory()->create(['branch_id' => $this->branch('RYD')->id]);
        $this->actingAs($manager)->post(route('admin.cases.decide', $case), ['decision' => 'transfer', 'new_sponsor_client_id' => $client->id, 'transfer_fee' => 2500])->assertSessionHas('success');
        $this->actingAs($this->branchManager())->post(route('admin.cases.complete', $case), ['completed_at' => today()->toDateString()])->assertSessionHas('success');

        $this->assertSame(CaseDecision::Transfer, $case->fresh()->decision);
        $this->assertSame($client->trans('name'), $case->fresh()->new_sponsor_name);
        $this->assertSame(WorkerStatus::Transferred, $worker->fresh()->status);
    }

    public function test_permissions_and_branch_scope(): void
    {
        $this->awaitingWorker('QSM');
        $qassimCase = WorkerCase::query()->sole();

        $this->actingAs($this->branchManager())->get(route('admin.cases.show', $qassimCase))->assertNotFound();
        $this->actingAs($this->branchManager())->post(route('admin.cases.decide', $qassimCase), ['decision' => 'return_to_work'])->assertNotFound();

        // The camp team sees cases but cannot decide.
        $this->awaitingWorker();
        $case = WorkerCase::query()->where('branch_id', $this->branch('RYD')->id)->sole();
        $campTeam = $this->userWithRole('camp_team', ['RYD']);
        $this->actingAs($campTeam)->get(route('admin.cases.index'))->assertOk()->assertSee($case->number);
        $this->actingAs($campTeam)->post(route('admin.cases.decide', $case), ['decision' => 'return_to_work'])->assertForbidden();
    }
}

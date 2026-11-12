<?php

namespace Tests\Feature\Services\Workers;

use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Events\WorkerStatusChanged;
use App\Models\Branch;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerStatusHistory;
use App\Services\Workers\InvalidStatusTransition;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\TestCase;

class WorkerStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private WorkerStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WorkerStatusService::class);
    }

    /**
     * Walk a worker to a status through allowed transitions.
     *
     * @param  list<WorkerStatus>  $path
     */
    private function walk(Worker $worker, array $path): Worker
    {
        foreach ($path as $status) {
            $this->service->transition($worker, $status);
        }

        return $worker;
    }

    private function arrivedWorker(SponsorType $sponsor): Worker
    {
        return $this->walk(Worker::factory()->create(['sponsor_type' => $sponsor]), [
            WorkerStatus::UnderReview, WorkerStatus::ReadyForSelection, WorkerStatus::OnHold,
            WorkerStatus::Contracted, WorkerStatus::InTravelProcess, WorkerStatus::Arrived,
        ]);
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $worker = Worker::factory()->create();

        $worker->update(['status' => 'available']);

        $this->assertSame(WorkerStatus::CandidateNew, $worker->fresh()->status);
    }

    public function test_status_cannot_be_changed_outside_the_service(): void
    {
        $worker = Worker::factory()->create();

        $this->expectException(LogicException::class);
        $worker->forceFill(['status' => WorkerStatus::Available])->save();
    }

    public function test_a_worker_cannot_be_created_with_another_status(): void
    {
        $this->expectException(LogicException::class);
        Worker::factory()->make()->forceFill(['status' => WorkerStatus::Available])->save();
    }

    public function test_not_allowed_transition_throws_and_changes_nothing(): void
    {
        $worker = Worker::factory()->create();

        try {
            $this->service->transition($worker, WorkerStatus::Available);
            $this->fail('Expected InvalidStatusTransition');
        } catch (InvalidStatusTransition $exception) {
            $this->assertSame(__('workers.status.not_allowed', ['from' => WorkerStatus::CandidateNew->label(), 'to' => WorkerStatus::Available->label()]), $exception->getMessage());
        }

        $this->assertSame(WorkerStatus::CandidateNew, $worker->fresh()->status);
        $this->assertSame(0, WorkerStatusHistory::query()->count());
    }

    public function test_final_statuses_have_no_way_out(): void
    {
        $worker = $this->walk($this->arrivedWorker(SponsorType::Company), [WorkerStatus::Available, WorkerStatus::RentedMonthly, WorkerStatus::Absconded]);

        $this->assertSame([], $this->service->allowedTransitions($worker));
        $this->expectException(InvalidStatusTransition::class);
        $this->service->transition($worker, WorkerStatus::Available);
    }

    public function test_arrived_to_delivered_is_blocked_for_company_sponsorship(): void
    {
        $worker = $this->arrivedWorker(SponsorType::Company);

        $this->assertNotContains(WorkerStatus::Delivered, $this->service->allowedTransitions($worker));
        $this->expectException(InvalidStatusTransition::class);
        $this->service->transition($worker, WorkerStatus::Delivered);
    }

    public function test_arrived_to_available_is_blocked_for_client_sponsorship(): void
    {
        $worker = $this->arrivedWorker(SponsorType::Client);

        $this->assertContains(WorkerStatus::Delivered, $this->service->allowedTransitions($worker));
        $this->expectException(InvalidStatusTransition::class);
        $this->service->transition($worker, WorkerStatus::Available);
    }

    public function test_every_transition_writes_history_and_fires_the_event(): void
    {
        Event::fake([WorkerStatusChanged::class]);
        $user = $this->actingAs(User::factory()->create())->app['auth']->user();
        $worker = Worker::factory()->create();
        $related = Branch::factory()->create();

        $this->service->transition($worker, WorkerStatus::UnderReview, 'Documents complete', $related);
        $this->service->transition($worker, WorkerStatus::Rejected, 'Failed interview');

        $this->assertSame(WorkerStatus::Rejected, $worker->status);
        $this->assertNotNull($worker->status_changed_at);

        $history = $worker->statusHistory()->get();
        $this->assertCount(2, $history);
        $first = $history->last();
        $this->assertSame(WorkerStatus::CandidateNew, $first->from_status);
        $this->assertSame(WorkerStatus::UnderReview, $first->to_status);
        $this->assertSame('Documents complete', $first->reason);
        $this->assertTrue($first->related->is($related));
        $this->assertSame($user->id, $first->changed_by);

        Event::assertDispatchedTimes(WorkerStatusChanged::class, 2);
        Event::assertDispatched(WorkerStatusChanged::class, fn (WorkerStatusChanged $event): bool => $event->from === WorkerStatus::CandidateNew
            && $event->to === WorkerStatus::UnderReview
            && $event->related?->is($related));
    }

    public function test_manual_transitions_are_limited_to_the_manual_list(): void
    {
        $candidate = Worker::factory()->create();
        $this->assertSame([], $this->service->manualTransitions($candidate));

        $available = $this->walk($this->arrivedWorker(SponsorType::Company), [WorkerStatus::Available]);
        $this->assertEqualsCanonicalizing(
            [WorkerStatus::Sick, WorkerStatus::Suspended, WorkerStatus::ExitReentry, WorkerStatus::AwaitingDecision],
            $this->service->manualTransitions($available),
        );

        // "available" is a manual choice only from sick / suspended / exit_reentry.
        $this->service->transition($available, WorkerStatus::Sick);
        $this->assertContains(WorkerStatus::Available, $this->service->manualTransitions($available));

        $rented = $this->walk($this->arrivedWorker(SponsorType::Company), [WorkerStatus::Available, WorkerStatus::RentedMonthly]);
        $this->assertNotContains(WorkerStatus::Available, $this->service->manualTransitions($rented));
        $this->assertContains(WorkerStatus::Available, $this->service->allowedTransitions($rented));
    }

    public function test_initial_status_is_limited_to_the_configured_list(): void
    {
        $worker = Worker::factory()->create(['sponsor_type' => SponsorType::Company]);

        $this->service->initialize($worker, WorkerStatus::Available, 'Existing worker');
        $this->assertSame(WorkerStatus::Available, $worker->status);
        $this->assertNull($worker->statusHistory()->sole()->from_status);

        $this->expectException(InvalidStatusTransition::class);
        $this->service->initialize(Worker::factory()->create(), WorkerStatus::Absconded);
    }
}

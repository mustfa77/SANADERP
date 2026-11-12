<?php

namespace Tests\Feature\Services\Recruitment;

use App\Enums\DeliveryMethod;
use App\Enums\LookupType;
use App\Enums\RecruitmentStatus;
use App\Enums\SponsorType;
use App\Enums\WorkerStatus;
use App\Models\Authority;
use App\Models\Camp;
use App\Models\Client;
use App\Models\Country;
use App\Models\CountryTravelStage;
use App\Models\Lookup;
use App\Models\Profession;
use App\Models\RecruitmentOrder;
use App\Models\Setting;
use App\Models\Worker;
use App\Services\Documents\CompletedVisasProvider;
use App\Services\Recruitment\RecruitmentException;
use App\Services\Recruitment\RecruitmentService;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class RecruitmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecruitmentService $service;

    private Country $country;

    private Profession $profession;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecruitmentService::class);
        $this->country = Country::factory()->create(['code' => 'PH']);
        $this->profession = Profession::factory()->create();
        $this->client = Client::factory()->create();

        CountryTravelStage::factory()->create(['country_id' => $this->country->id, 'name_en' => 'Medical', 'max_days' => 7]);
        CountryTravelStage::factory()->create(['country_id' => $this->country->id, 'name_en' => 'Visa stamping', 'max_days' => 10]);
        CountryTravelStage::factory()->create(['country_id' => $this->country->id, 'name_en' => 'Old stage', 'is_active' => false]);
    }

    private function order(): RecruitmentOrder
    {
        return $this->service->create([
            'branch_id' => $this->client->branch_id,
            'client_id' => $this->client->id,
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
        ]);
    }

    /**
     * A screened candidate, ready for selection.
     */
    private function candidate(array $attributes = []): Worker
    {
        $worker = Worker::factory()->create([
            'country_id' => $this->country->id,
            'profession_id' => $this->profession->id,
            'sponsor_type' => SponsorType::Client,
            ...$attributes,
        ]);

        $statuses = app(WorkerStatusService::class);
        $statuses->transition($worker, WorkerStatus::UnderReview);
        $statuses->transition($worker, WorkerStatus::ReadyForSelection);

        return $worker;
    }

    /**
     * @return array<string, string>
     */
    private function contractData(string $visa): array
    {
        return ['visa_number' => $visa, 'visa_issued_at' => '2026-10-20', 'musaned_contract_no' => 'MS-'.$visa, 'contracted_at' => now()->toDateString()];
    }

    private function contracted(string $visa = '1900000001'): RecruitmentOrder
    {
        $order = $this->service->selectCandidate($this->order(), $this->candidate());

        return $this->service->contract($order, $this->contractData($visa));
    }

    public function test_numbers_are_sequential_per_year(): void
    {
        $prefix = 'RQ-'.now()->format('Y').'-';

        $this->assertSame($prefix.'0001', $this->order()->number);
        $this->assertSame($prefix.'0002', $this->order()->number);
    }

    public function test_full_lifecycle_moves_the_worker_status_and_links_history_to_the_order(): void
    {
        $worker = $this->candidate();

        $order = $this->service->selectCandidate($this->order(), $worker);
        $this->assertSame(RecruitmentStatus::CandidateSelected, $order->status);
        $this->assertSame(WorkerStatus::OnHold, $worker->fresh()->status);

        $order = $this->service->contract($order, [...$this->contractData('1900000001'), 'contracted_at' => '2026-11-01']);
        $this->assertSame(WorkerStatus::Contracted, $worker->fresh()->status);
        $this->assertSame('1900000001', $order->visa_number);
        $this->assertSame('2026-10-20', $order->visa_issued_at->toDateString());

        $order = $this->service->startTravel($order, '2026-11-02');
        $this->assertSame(WorkerStatus::InTravelProcess, $worker->fresh()->status);
        $stages = $order->stages()->get();
        $this->assertSame(['Medical', 'Visa stamping'], $stages->pluck('name_en')->all());
        $this->assertSame('2026-11-09', $stages[0]->due_at->toDateString());
        $this->assertNull($stages[1]->started_at);

        $order = $this->service->completeStage($order, $stages[0], '2026-11-05');
        $second = $stages[1]->fresh();
        $this->assertSame('2026-11-05', $second->started_at->toDateString());
        $this->assertSame('2026-11-15', $second->due_at->toDateString());

        $order = $this->service->completeStage($order, $second, '2026-11-10');
        $order = $this->service->markArrived($order, '2026-11-11');
        $this->assertSame(WorkerStatus::Arrived, $worker->fresh()->status);

        $order = $this->service->deliver($order, $this->deliveryData('2026-11-12'));
        $this->assertSame(RecruitmentStatus::Delivered, $order->status);
        $this->assertSame(WorkerStatus::InProbation, $worker->fresh()->status);

        $order = $this->service->completeProbation($order, '2026-11-12');
        $this->assertSame(RecruitmentStatus::Completed, $order->status);
        $this->assertSame(WorkerStatus::ProbationCompleted, $worker->fresh()->status);

        $linked = $worker->statusHistory()->where('related_type', $order->getMorphClass())->where('related_id', $order->id)->count();
        // on_hold, contracted, in_travel_process, arrived, delivered, in_probation, probation_completed.
        $this->assertSame(7, $linked);
    }

    /**
     * @return array<string, mixed>
     */
    private function deliveryData(string $date, array $overrides = []): array
    {
        return ['delivered_at' => $date, 'delivery_method' => 'office', 'receiver_name' => 'Fahad', ...$overrides];
    }

    /**
     * An order whose worker arrived on $arrivedAt, housed in $camp.
     */
    private function arrived(string $arrivedAt, ?Camp $camp = null, string $visa = '1900000001'): RecruitmentOrder
    {
        $order = $this->service->startTravel($this->contracted($visa), $arrivedAt);

        foreach ($order->stages()->get() as $stage) {
            $order = $this->service->completeStage($order, $stage, $arrivedAt);
        }

        return $this->service->markArrived($order, $arrivedAt, $camp);
    }

    public function test_arrival_houses_the_worker_in_a_camp_of_the_orders_branch(): void
    {
        $camp = Camp::factory()->create(['branch_id' => $this->client->branch_id, 'capacity' => 1]);
        $order = $this->arrived('2026-11-01', $camp);

        $this->assertTrue($order->worker->fresh()->camp->is($camp));
        $this->assertSame($camp->id, $order->arrival_camp_id);
        $this->assertSame(1, $this->service->campOccupancy($camp));

        // Full camp, and a camp of another branch, are refused.
        $order2 = $this->service->startTravel($this->contracted('1900000002'), '2026-11-01');
        foreach ($order2->stages()->get() as $stage) {
            $order2 = $this->service->completeStage($order2, $stage, '2026-11-01');
        }

        foreach ([$camp, Camp::factory()->create()] as $refused) {
            try {
                $this->service->markArrived($order2, '2026-11-01', $refused);
                $this->fail('Expected RecruitmentException');
            } catch (RecruitmentException) {
                $this->assertSame(RecruitmentStatus::InTravel, $order2->fresh()->status);
            }
        }
    }

    public function test_delivery_charges_camp_days_after_the_free_days_and_the_home_fee(): void
    {
        Setting::put('camp_free_days', '1');
        Setting::put('camp_daily_fee', '40');
        Setting::put('home_delivery_fee', '120');
        Setting::put('probation_days', '90');
        $camp = Camp::factory()->create(['branch_id' => $this->client->branch_id]);
        $order = $this->arrived('2026-11-01', $camp);

        // 4 days in the camp, 1 free: 3 × 40 = 120, + 120 home delivery.
        $quote = $this->service->deliveryQuote($order, '2026-11-05', DeliveryMethod::Home);
        $this->assertSame(4, $quote['camp_days']);
        $this->assertEquals(120, $quote['camp_fee']);
        $this->assertEquals(240, $quote['total']);
        $this->assertEquals(0, $this->service->deliveryQuote($order, '2026-11-05', DeliveryMethod::Office)['delivery_fee']);

        $order = $this->service->deliver($order, $this->deliveryData('2026-11-05', ['delivery_method' => 'home', 'receiver_phone' => '0551234567']));

        $this->assertSame(DeliveryMethod::Home, $order->delivery_method);
        $this->assertSame(4, $order->camp_days);
        $this->assertSame('120.00', $order->camp_fee);
        $this->assertSame('120.00', $order->delivery_fee);
        $this->assertSame('2027-02-03', $order->probation_ends_at->toDateString());
        // She leaves the camp.
        $this->assertNull($order->worker->fresh()->camp_id);
    }

    public function test_fees_can_be_changed_or_waived_and_no_camp_means_no_camp_fee(): void
    {
        $order = $this->arrived('2026-11-01');

        $this->assertSame(0, $this->service->deliveryQuote($order, '2026-11-20', DeliveryMethod::Office)['camp_days']);

        $order = $this->service->deliver($order, $this->deliveryData('2026-11-20', ['delivery_method' => 'home', 'delivery_fee' => '0']));
        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('0.00', $order->camp_fee);
    }

    public function test_delivery_cannot_be_before_arrival(): void
    {
        $order = $this->arrived('2026-11-05');

        $this->expectException(RecruitmentException::class);
        $this->service->deliver($order, $this->deliveryData('2026-11-04'));
    }

    public function test_return_during_probation_waits_for_a_decision_or_marks_absconded(): void
    {
        $reason = Lookup::factory()->create(['type' => LookupType::ReturnReason]);

        $order = $this->service->deliver($this->arrived('2026-11-01'), $this->deliveryData('2026-11-02'));
        $order = $this->service->returnDuringProbation($order, '2026-11-10', $reason, note: 'Client not satisfied');
        $this->assertSame(RecruitmentStatus::Returned, $order->status);
        $this->assertSame(WorkerStatus::AwaitingDecision, $order->worker->fresh()->status);
        $this->assertTrue($reason->isInUse());

        $other = $this->service->deliver($this->arrived('2026-11-01', visa: '1900000005'), $this->deliveryData('2026-11-02'));
        $other = $this->service->returnDuringProbation($other, '2026-11-03', $reason, absconded: true);
        $this->assertSame(WorkerStatus::Absconded, $other->worker->fresh()->status);

        // Returned / completed orders are closed.
        $this->expectException(RecruitmentException::class);
        $this->service->completeProbation($order, '2026-11-11');
    }

    public function test_only_eligible_candidates_can_be_selected(): void
    {
        $order = $this->order();
        $companyWorker = $this->candidate(['sponsor_type' => SponsorType::Company]);
        $otherCountry = $this->candidate(['country_id' => Country::factory()->create()->id]);
        $notScreened = Worker::factory()->create(['country_id' => $this->country->id, 'profession_id' => $this->profession->id, 'sponsor_type' => SponsorType::Client]);
        $eligible = $this->candidate();

        $this->assertSame([$eligible->id], $this->service->candidatesFor($order)->pluck('id')->all());

        foreach ([$companyWorker, $otherCountry, $notScreened] as $worker) {
            try {
                $this->service->selectCandidate($order, $worker);
                $this->fail('Expected RecruitmentException');
            } catch (RecruitmentException) {
                $this->assertSame(RecruitmentStatus::New, $order->fresh()->status);
            }
        }
    }

    public function test_a_selected_candidate_is_not_offered_to_another_order(): void
    {
        $worker = $this->candidate();
        $this->service->selectCandidate($this->order(), $worker);

        $this->assertCount(0, $this->service->candidatesFor($this->order())->get());
    }

    public function test_release_and_cancel_return_the_candidate_to_ready_for_selection(): void
    {
        $worker = $this->candidate();
        $order = $this->service->selectCandidate($this->order(), $worker);

        $order = $this->service->releaseCandidate($order, 'Client changed mind');
        $this->assertSame(RecruitmentStatus::New, $order->status);
        $this->assertNull($order->worker_id);
        $this->assertSame(WorkerStatus::ReadyForSelection, $worker->fresh()->status);

        $order = $this->service->selectCandidate($order, $worker);
        $reason = Lookup::factory()->create(['type' => LookupType::CancelReason]);
        $order = $this->service->cancel($order, $reason, 'No longer needed');

        $this->assertSame(RecruitmentStatus::Cancelled, $order->status);
        $this->assertSame(WorkerStatus::ReadyForSelection, $worker->fresh()->status);
    }

    public function test_cannot_cancel_after_the_contract(): void
    {
        $order = $this->contracted();

        $this->expectException(RecruitmentException::class);
        $this->service->cancel($order, Lookup::factory()->create(['type' => LookupType::CancelReason]));
    }

    public function test_a_client_may_have_several_orders_each_with_its_own_visa(): void
    {
        $this->contracted('1900000001');
        $this->contracted('1900000002');

        $this->assertSame(2, $this->client->recruitmentOrders()->count());

        // The same visa number cannot be used twice.
        $order = $this->service->selectCandidate($this->order(), $this->candidate());

        try {
            $this->service->contract($order, $this->contractData('1900000001'));
            $this->fail('Expected RecruitmentException');
        } catch (RecruitmentException) {
            $this->assertSame(RecruitmentStatus::CandidateSelected, $order->fresh()->status);
        }
    }

    public function test_stages_must_be_completed_in_order_before_arrival(): void
    {
        $order = $this->service->startTravel($this->contracted());
        [$first, $second] = $order->stages()->get()->all();

        try {
            $this->service->completeStage($order, $second, now()->toDateString());
            $this->fail('Expected RecruitmentException');
        } catch (RecruitmentException) {
            $this->assertNull($second->fresh()->completed_at);
        }

        $this->expectException(RecruitmentException::class);
        $this->service->markArrived($order, now()->toDateString());
    }

    public function test_order_status_cannot_be_changed_outside_the_service(): void
    {
        $order = $this->order();

        $this->expectException(LogicException::class);
        $order->forceFill(['status' => RecruitmentStatus::Delivered])->save();
    }

    public function test_completed_visas_provider_lists_arrived_orders_of_the_country(): void
    {
        $order = $this->contracted();
        $order = $this->service->startTravel($order);

        foreach ($order->stages()->get() as $stage) {
            $order = $this->service->completeStage($order, $stage, now()->toDateString());
        }

        $this->service->markArrived($order, now()->toDateString());
        $this->contracted('1900000099'); // Contracted only: not completed yet.

        $authority = Authority::factory()->create(['country_id' => $this->country->id]);
        $visas = app(CompletedVisasProvider::class)->latest($authority, $this->country, 30);

        $this->assertCount(1, $visas);
        $this->assertSame('1900000001', $visas[0]['visa_number']);
        $this->assertSame('2026-10-20', $visas[0]['issued_at']);
    }
}
